<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\Ingestion\EventCategorizationService;
use App\Services\Ingestion\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Post-traitement des events ingerés (status='ingested') :
 *
 *  1. Geocoding : si latitude/longitude manquantes, appelle Nominatim
 *     via GeocodingService (rate limit 1 req/s respecte). En mode
 *     fixture : skip (les events ont deja leurs coords).
 *
 *  2. Categorisation : EventCategorizationService applique les regles
 *     mots-cles config('bia.categorization_rules'). Si rien ne matche,
 *     on garde la categorie source telle quelle (sera trancher par
 *     Claude en S2 sur les cas ambigus).
 *
 *  3. Dedoublonnage : pour chaque event, on cherche un autre event de
 *     la meme journee dans la meme ville avec un titre similaire
 *     (similar_text > 85%). Si on trouve, le plus recent est garde
 *     (status=normalized) et le doublon passe en status=dropped avec
 *     un log indiquant le master.
 *
 *  4. Status final : 'normalized' si tout OK, 'dropped' si doublon.
 *
 * Idempotent : peut etre relance plusieurs fois sans effet de bord.
 * Job typiquement lance 15min apres IngestOpenDataJob (cf. scheduler).
 */
class NormalizeEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 70% applique sur (title + venue), seuil plus permissif que sur le
    // seul titre car on combine 2 signaux. Volontairement faux-positif >
    // faux-negatif : mieux vaut un brief qui rate 1 event qu'un brief
    // avec un doublon.
    public const SIMILARITY_THRESHOLD = 70.0;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(
        public readonly ?string $citySlug = null,
    ) {}

    public function handle(GeocodingService $geocoder, EventCategorizationService $categorizer): array
    {
        $stats = ['processed' => 0, 'geocoded' => 0, 'categorized' => 0, 'dropped_duplicates' => 0, 'normalized' => 0];

        $query = Event::query()
            ->where('status', Event::STATUS_INGESTED)
            ->orderBy('starts_at');

        if ($this->citySlug) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $this->citySlug));
        }

        $events = $query->get();

        foreach ($events as $event) {
            $stats['processed']++;

            // 1. Geocoding si necessaire
            if (($event->latitude === null || $event->longitude === null) && ! empty($event->address)) {
                $coords = $geocoder->geocode($event->address);
                if ($coords !== null) {
                    // event n'a pas latitude/longitude dans schema, on log juste
                    $stats['geocoded']++;
                }
            }

            // 2. Categorisation
            $existingCategory = is_array($event->category) ? ($event->category[0] ?? null) : null;
            $autoCategory = $categorizer->categorize($event->title, $event->description, $existingCategory);
            if ($autoCategory && $autoCategory !== $existingCategory) {
                $event->category = array_values(array_unique(array_filter([$existingCategory, $autoCategory])));
                $stats['categorized']++;
            }

            // 3. Dedoublonnage
            $duplicate = $this->findDuplicate($event);
            if ($duplicate !== null) {
                $event->status = Event::STATUS_DROPPED;
                $event->save();
                $stats['dropped_duplicates']++;
                Log::channel('ingestion')->info('event.duplicate_dropped', [
                    'event_id' => $event->id,
                    'master_id' => $duplicate->id,
                    'title' => $event->title,
                ]);

                continue;
            }

            // 4. OK — normalized
            $event->status = Event::STATUS_NORMALIZED;
            $event->save();
            $stats['normalized']++;
        }

        Log::channel('ingestion')->info('events.normalize_complete', $stats);

        return $stats;
    }

    /**
     * Cherche un doublon : autre event de la meme journee, meme ville,
     * titre similaire (similar_text > 85%), deja status=normalized OU
     * d'une source consideree primaire (manual > opendata > rss > scraping).
     */
    protected function findDuplicate(Event $event): ?Event
    {
        $sameDay = Event::query()
            ->where('city_id', $event->city_id)
            ->where('id', '!=', $event->id)
            ->where('status', Event::STATUS_NORMALIZED)
            ->whereDate('starts_at', $event->starts_at?->toDateString())
            ->get();

        $eventKey = mb_strtolower(trim($event->title . ' ' . ($event->venue_name ?? '')));

        foreach ($sameDay as $candidate) {
            $candidateKey = mb_strtolower(trim($candidate->title . ' ' . ($candidate->venue_name ?? '')));

            similar_text($eventKey, $candidateKey, $similarity);

            if ($similarity >= self::SIMILARITY_THRESHOLD) {
                return $candidate;
            }
        }

        return null;
    }
}
