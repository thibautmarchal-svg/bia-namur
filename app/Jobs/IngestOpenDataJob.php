<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\Event;
use App\Services\Ingestion\OpenDataNamurService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ingere les events depuis l'API OpenData Namur.
 *
 * Chaque event est insere ou mis a jour via Event::updateOrCreate
 * sur (source, external_id) — idempotent. Status='ingested' apres
 * insertion ; le NormalizeEventsJob (lance dans la foulee) passera
 * a 'normalized' apres dedup + geocoding + categorisation.
 *
 * Aucun plantage si OpenData renvoie un payload vide ou invalide :
 * tout est logge dans le channel 'ingestion'. La feature flag
 * config('bia.sources.opendata_namur') permet de couper la source en
 * urgence sans deploy.
 */
class IngestOpenDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];   // 30s, 2min, 10min

    public function __construct(
        public readonly string $citySlug = 'namur',
    ) {}

    public function handle(OpenDataNamurService $service): array
    {
        if (! config('bia.sources.opendata_namur', true)) {
            Log::channel('ingestion')->info('opendata.skipped_disabled');

            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $city = City::where('slug', $this->citySlug)->firstOrFail();

        $events = $service->fetchEvents();
        if (empty($events)) {
            Log::channel('ingestion')->warning('opendata.empty_payload', ['city' => $this->citySlug]);

            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($events as $payload) {
            try {
                $startsAt = $this->parseDate($payload['starts_at']);
                if ($startsAt === null) {
                    $stats['skipped']++;

                    continue;
                }

                $existing = Event::where('source', OpenDataNamurService::SOURCE)
                    ->where('external_id', $payload['external_id'])
                    ->first();

                $attributes = [
                    'city_id' => $city->id,
                    'source' => OpenDataNamurService::SOURCE,
                    'external_id' => $payload['external_id'],
                    'title' => $payload['title'],
                    'description' => $payload['description'],
                    'starts_at' => $startsAt,
                    'ends_at' => $this->parseDate($payload['ends_at']),
                    'venue_name' => $payload['venue'],
                    'address' => $payload['address'],
                    'category' => $payload['category'] ? [$payload['category']] : null,
                    'price_info' => $payload['price_info'],
                    'url' => $payload['url'],
                    'raw_payload' => $payload['raw'],
                    'ingested_at' => now(),
                    'status' => Event::STATUS_INGESTED,
                ];

                if ($existing) {
                    $existing->update($attributes);
                    $stats['updated']++;
                } else {
                    Event::create($attributes);
                    $stats['inserted']++;
                }
            } catch (\Throwable $e) {
                Log::channel('ingestion')->error('opendata.event_insert_failed', [
                    'external_id' => $payload['external_id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
                $stats['skipped']++;
            }
        }

        Log::channel('ingestion')->info('opendata.ingestion_complete', $stats);

        return $stats;
    }

    protected function parseDate(?string $raw): ?Carbon
    {
        if (empty($raw)) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
