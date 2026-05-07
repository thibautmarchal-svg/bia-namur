<?php

namespace App\Jobs;

use App\Models\AiRun;
use App\Models\Brief;
use App\Models\BriefItem;
use App\Models\City;
use App\Models\Event;
use App\Services\Ai\ClaudeApiService;
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
 *  1. Selectionne les events normalises de la semaine cible
 *  2. Construit le payload utilisateur pour Claude (events + meta)
 *  3. Appelle ClaudeApiService::complete('brief_v1', $payload)
 *  4. Parse le JSON retourne, cree Brief + BriefItem en transaction
 *  5. Status = draft_ai (necessite relecture humaine avant publication)
 *
 * En S1 : execute en mode mock (BIA_AI_MOCK_MODE=true) → fixture
 *         tests/Fixtures/claude/brief_mock_v1.json. Pas d'appel reseau.
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
    ) {}

    public function handle(ClaudeApiService $claude): Brief
    {
        $city = City::where('slug', $this->citySlug)->firstOrFail();

        $weekStart = Carbon::now()->setISODate($this->year, $this->weekNumber)->startOfWeek();
        $weekEnd = (clone $weekStart)->endOfWeek();

        $events = Event::query()
            ->where('city_id', $city->id)
            ->whereIn('status', [Event::STATUS_NORMALIZED, Event::STATUS_INGESTED])
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->orderBy('starts_at')
            ->get();

        $userPayload = $this->buildUserPayload($events, $weekStart, $weekEnd);
        $briefSlug = sprintf('%d-W%02d', $this->year, $this->weekNumber);

        Log::info('brief.generation.started', [
            'city' => $city->slug,
            'year' => $this->year,
            'week' => $this->weekNumber,
            'events_count' => $events->count(),
        ]);

        $completion = $claude->complete(
            promptKey: 'brief_v1',
            userMessage: $userPayload,
            logContext: ['related_type' => Brief::class],
        );

        $payload = $completion->toJson();

        return DB::transaction(function () use ($city, $briefSlug, $payload, $completion, $events) {
            $brief = Brief::updateOrCreate(
                [
                    'city_id' => $city->id,
                    'year' => $this->year,
                    'week_number' => $this->weekNumber,
                ],
                [
                    'slug' => $briefSlug,
                    'title' => "Cette semaine à {$city->name} — semaine {$this->weekNumber}",
                    'intro_text' => $payload['intro'] ?? null,
                    'generated_at' => now(),
                    'status' => Brief::STATUS_DRAFT_AI,
                    'selected_event_ids' => collect($payload['items'] ?? [])
                        ->pluck('event_id')
                        ->filter()
                        ->values()
                        ->all(),
                ],
            );

            // Reset des items existants si re-generation
            $brief->items()->delete();

            $eventLookup = $events->keyBy('id');

            foreach ($payload['items'] ?? [] as $position => $item) {
                $eventId = $item['event_id'] ?? null;
                $linkedEvent = $eventId ? $eventLookup->get($eventId) : null;

                BriefItem::create([
                    'brief_id' => $brief->id,
                    'event_id' => $linkedEvent?->id,
                    'place_id' => $linkedEvent?->place_id,
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
     */
    protected function buildUserPayload($events, Carbon $weekStart, Carbon $weekEnd): string
    {
        $eventsForPrompt = $events->map(fn (Event $e) => [
            'id' => $e->id,
            'title' => $e->title,
            'description' => mb_substr((string) $e->description, 0, 400),
            'starts_at' => $e->starts_at?->toIso8601String(),
            'ends_at' => $e->ends_at?->toIso8601String(),
            'venue' => $e->venue_name,
            'category' => $e->category,
            'price_info' => $e->price_info,
            'source' => $e->source,
        ])->toArray();

        return json_encode([
            'date_debut' => $weekStart->toDateString(),
            'date_fin' => $weekEnd->toDateString(),
            'events' => $eventsForPrompt,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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
