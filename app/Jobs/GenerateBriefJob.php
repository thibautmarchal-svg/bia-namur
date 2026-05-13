<?php

namespace App\Jobs;

use App\Models\AiRun;
use App\Models\Brief;
use App\Models\BriefItem;
use App\Models\City;
use App\Services\Ai\ClaudeApiService;
use App\Services\Ingestion\NamurAgendaRssService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Genere le brief hebdo d'une ville pour une semaine ISO.
 *
 * Flux (cf. brief §7.1) :
 *  1. Fetch le flux RSS officiel namur.be (NamurAgendaRssService)
 *  2. Construit le payload utilisateur pour Claude (items RSS + meta semaine)
 *  3. Appelle ClaudeApiService::complete('brief_v1', $payload)
 *  4. Parse le JSON retourne, cree Brief + BriefItem en transaction
 *  5. Status = draft_ai (necessite relecture humaine avant publication)
 *
 * Note J27 : on n'utilise PLUS la table events car aucune source structuree
 * ne fonctionne (OpenData v2 dataset gele en 2018, RSS culturels en 404).
 * Le RSS namur.be est la seule source vivante et ses items n'ont pas de
 * date d'evenement structuree (uniquement dc:date = date de publication).
 * Claude se debrouille pour decoder la date depuis le titre/description.
 *
 * Re-generation safe : si un brief existe pour cette semaine (meme soft-
 * deleted), il est restaure et mis a jour plutot que d'echouer sur la
 * contrainte unique (city_id, year, week_number).
 *
 * Retry : 3 tentatives avec backoff exponentiel (Laravel queue auto).
 */
class GenerateBriefJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 180];   // 10s, 1min, 3min

    public function __construct(
        public readonly string $citySlug,
        public readonly int $year,
        public readonly int $weekNumber,
        public readonly ?string $modelOverride = null,
    ) {}

    public function handle(ClaudeApiService $claude, NamurAgendaRssService $rss): Brief
    {
        $city = City::where('slug', $this->citySlug)->firstOrFail();

        $weekStart = Carbon::now()->setISODate($this->year, $this->weekNumber)->startOfWeek();
        $weekEnd = (clone $weekStart)->endOfWeek();

        $items = $rss->fetchItems(50);

        $userPayload = $this->buildUserPayload($items, $weekStart, $weekEnd);
        $briefSlug = sprintf('%d-W%02d', $this->year, $this->weekNumber);

        Log::info('brief.generation.started', [
            'city' => $city->slug,
            'year' => $this->year,
            'week' => $this->weekNumber,
            'rss_items_count' => count($items),
        ]);

        if (empty($items)) {
            Log::warning('brief.generation.no_items', [
                'city' => $city->slug,
                'year' => $this->year,
                'week' => $this->weekNumber,
            ]);
        }

        $completion = $claude->complete(
            promptKey: 'brief_v1',
            userMessage: $userPayload,
            modelOverride: $this->modelOverride,
            logContext: ['related_type' => Brief::class],
        );

        $payload = $completion->toJson();

        return DB::transaction(function () use ($city, $briefSlug, $payload, $completion) {
            $brief = Brief::withTrashed()
                ->where('city_id', $city->id)
                ->where('year', $this->year)
                ->where('week_number', $this->weekNumber)
                ->first();

            $attributes = [
                'city_id' => $city->id,
                'year' => $this->year,
                'week_number' => $this->weekNumber,
                'slug' => $briefSlug,
                'title' => "Cette semaine à {$city->name} — semaine {$this->weekNumber}",
                'intro_text' => $payload['intro'] ?? null,
                'generated_at' => now(),
                'status' => Brief::STATUS_DRAFT_AI,
            ];

            if ($brief) {
                if ($brief->trashed()) {
                    $brief->restore();
                }
                $brief->update($attributes);
            } else {
                $brief = Brief::create($attributes);
            }

            // Reset des items existants si re-generation
            $brief->items()->delete();

            foreach ($payload['items'] ?? [] as $position => $item) {
                BriefItem::create([
                    'brief_id' => $brief->id,
                    'position' => $position + 1,
                    'ai_text' => $this->formatItemAsMarkdown($item),
                    'reasoning' => [
                        'venue' => $item['venue'] ?? null,
                        'when_text' => $item['when_text'] ?? null,
                        'reasoning' => $item['reasoning'] ?? null,
                        'ai_run_id' => $completion->aiRunId,
                    ],
                ]);
            }

            // Lien polymorphique : ai_runs.related → ce brief
            if ($completion->aiRunId) {
                AiRun::where('id', $completion->aiRunId)->update([
                    'related_type' => Brief::class,
                    'related_id' => $brief->id,
                ]);
            }

            Log::info('brief.generation.success', [
                'brief_id' => $brief->id,
                'items_count' => count($payload['items'] ?? []),
                'is_mock' => $completion->isMock,
            ]);

            return $brief;
        });
    }

    /**
     * Construit le payload texte qui sera envoye a Claude comme user message.
     * Aucun PII utilisateur, uniquement des donnees publiques d'events.
     *
     * @param  array<int, array{title:string, link:string, description:string, date:?string}>  $items
     */
    protected function buildUserPayload(array $items, Carbon $weekStart, Carbon $weekEnd): string
    {
        $now = now('Europe/Brussels');

        $payload = [
            'date_aujourdhui' => $now->toDateString(),
            'semaine_debut' => $weekStart->toDateString(),
            'semaine_fin' => $weekEnd->toDateString(),
            'events_disponibles' => $items,
        ];

        return "Voici les events publiés par la Ville de Namur sur son agenda officiel.\n\n"
            . "DATE AUJOURD'HUI : {$payload['date_aujourdhui']}\n"
            . "SEMAINE BRIEF : du {$payload['semaine_debut']} au {$payload['semaine_fin']}\n\n"
            . "INSTRUCTION : sélectionne 5 à 7 events qui se passent CETTE SEMAINE OU TRÈS BIENTÔT "
            . "(les events ont leur date dans le titre ou la description — utilise ces indices).\n"
            . "Diversifie les types : concert, expo, balade nature, théâtre, marché, gastronomie, patrimoine.\n"
            . "Rédige le brief Bia Namur (intro + items + outro) en respectant le ton namurois chaleureux.\n\n"
            . "DONNÉES :\n"
            . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function formatItemAsMarkdown(array $item): string
    {
        $parts = [];
        $parts[] = '**' . ($item['title'] ?? '') . '**';
        if (! empty($item['venue'])) {
            $parts[] = $item['venue'];
        }
        if (! empty($item['when_text'])) {
            $parts[] = '_' . $item['when_text'] . '_';
        }
        if (! empty($item['angle'])) {
            $parts[] = '';
            $parts[] = $item['angle'];
        }

        return implode("\n", $parts);
    }
}
