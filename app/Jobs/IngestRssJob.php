<?php

namespace App\Jobs;

use App\Models\City;
use App\Models\Event;
use App\Services\Ingestion\RssIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ingere tous les feeds RSS / Atom configures dans config/bia.rss_feeds,
 * filtres par les feature flags config/bia.sources.{slug}.
 *
 * Chaque event est insere ou mis a jour via Event::updateOrCreate sur
 * (source, external_id) — idempotent. Un feed coupe par feature flag
 * (BIA_SRC_RSS_DELTA=false par exemple) est skip silencieusement avec
 * un log info.
 *
 * Si un feed echoue (XML invalide, indispo HTTP), seul ce feed est
 * skippe — les autres continuent d'ingerer normalement.
 */
class IngestRssJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 1200];   // 1min, 5min, 20min

    public function __construct(
        public readonly string $citySlug = 'namur',
    ) {}

    public function handle(RssIngestService $service): array
    {
        $city = City::where('slug', $this->citySlug)->firstOrFail();
        $feeds = config('bia.rss_feeds', []);

        $totalStats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'feeds_active' => 0];

        foreach ($feeds as $feedSlug => $feedConfig) {
            // Feature flag par feed : config/bia.sources.{slug}
            if (! config("bia.sources.{$feedSlug}", true)) {
                Log::channel('ingestion')->info('rss.feed_disabled', ['feed' => $feedSlug]);

                continue;
            }

            $totalStats['feeds_active']++;

            $events = $service->fetchFromFeed($feedSlug);
            if (empty($events)) {
                Log::channel('ingestion')->warning('rss.feed_empty', ['feed' => $feedSlug]);

                continue;
            }

            foreach ($events as $payload) {
                try {
                    $startsAt = $this->parseDate($payload['starts_at']);
                    if ($startsAt === null) {
                        $totalStats['skipped']++;

                        continue;
                    }

                    $existing = Event::where('source', $feedSlug)
                        ->where('external_id', $payload['external_id'])
                        ->first();

                    $attributes = [
                        'city_id' => $city->id,
                        'source' => $feedSlug,
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
                        $totalStats['updated']++;
                    } else {
                        Event::create($attributes);
                        $totalStats['inserted']++;
                    }
                } catch (\Throwable $e) {
                    Log::channel('ingestion')->error('rss.event_insert_failed', [
                        'feed' => $feedSlug,
                        'external_id' => $payload['external_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $totalStats['skipped']++;
                }
            }
        }

        Log::channel('ingestion')->info('rss.ingestion_complete', $totalStats);

        return $totalStats;
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
