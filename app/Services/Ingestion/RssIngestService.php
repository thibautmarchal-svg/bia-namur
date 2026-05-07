<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

/**
 * Parse les flux RSS 2.0 et Atom des sites culturels namurois.
 *
 * Mode fixture (BIA_INGEST_FIXTURE_MODE=true) : lit les snapshots locaux
 *   tests/Fixtures/rss/{slug}.xml. Active par defaut en local + testing.
 *
 * Mode HTTP : Http::get avec User-Agent identifie, timeout 30s.
 *
 * Compatible RSS 2.0 (rss/channel/item) ET Atom 1.0 (feed/entry). Les
 * dates d'evenement sont cherchees dans dc:date, eventDate, published,
 * pubDate (dans cet ordre de priorite). Si rien : on prend updated.
 *
 * Ne plante jamais : XML invalide, feed indispo, format inattendu →
 * tout est attrape, log dans channel 'ingestion', retourne array vide.
 */
class RssIngestService
{
    /**
     * @return array<int, array{external_id:string, title:string, description:?string, starts_at:?string, ends_at:?string, venue:?string, address:?string, latitude:?float, longitude:?float, category:?string, price_info:?string, url:?string, raw:array}>
     */
    public function fetchFromFeed(string $feedSlug): array
    {
        $feedConfig = config("bia.rss_feeds.{$feedSlug}");
        if (! is_array($feedConfig) || empty($feedConfig)) {
            Log::channel('ingestion')->warning('rss.unknown_feed', ['slug' => $feedSlug]);

            return [];
        }

        $xmlString = config('bia.ingestion.fixture_mode')
            ? $this->loadFixture($feedConfig)
            : $this->loadHttp($feedConfig);

        if (empty($xmlString)) {
            return [];
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlString);
        } catch (Throwable $e) {
            Log::channel('ingestion')->error('rss.parse_error', [
                'feed' => $feedSlug,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $events = $this->isAtom($xml)
            ? $this->parseAtom($xml, $feedConfig, $feedSlug)
            : $this->parseRss($xml, $feedConfig, $feedSlug);

        Log::channel('ingestion')->info('rss.fetched', [
            'feed' => $feedSlug,
            'count' => count($events),
            'mode' => config('bia.ingestion.fixture_mode') ? 'fixture' : 'http',
        ]);

        return $events;
    }

    protected function loadFixture(array $feedConfig): string
    {
        $path = base_path('tests/Fixtures/rss/' . ($feedConfig['fixture'] ?? ''));
        if (! is_file($path)) {
            return '';
        }

        return file_get_contents($path) ?: '';
    }

    protected function loadHttp(array $feedConfig): string
    {
        $url = $feedConfig['url'] ?? null;
        if (empty($url)) {
            return '';
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => config('bia.ingestion.user_agent'),
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml',
            ])
                ->timeout((int) config('bia.ingestion.http_timeout_seconds', 30))
                ->get($url);

            if (! $response->successful()) {
                Log::channel('ingestion')->warning('rss.http_error', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return '';
            }

            return $response->body();
        } catch (Throwable $e) {
            Log::channel('ingestion')->error('rss.http_exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function isAtom(SimpleXMLElement $xml): bool
    {
        return $xml->getName() === 'feed';
    }

    protected function parseRss(SimpleXMLElement $xml, array $feedConfig, string $feedSlug): array
    {
        $events = [];
        $items = $xml->channel->item ?? null;
        if (! $items) {
            return [];
        }

        foreach ($items as $item) {
            $event = $this->normalizeItem($item, $feedConfig, $feedSlug, isAtom: false);
            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }

    protected function parseAtom(SimpleXMLElement $xml, array $feedConfig, string $feedSlug): array
    {
        $events = [];
        foreach ($xml->entry as $entry) {
            $event = $this->normalizeItem($entry, $feedConfig, $feedSlug, isAtom: true);
            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }

    protected function normalizeItem(SimpleXMLElement $item, array $feedConfig, string $feedSlug, bool $isAtom): ?array
    {
        $title = trim((string) ($item->title ?? ''));
        if ($title === '') {
            return null;
        }

        // External ID : guid > id > link
        $externalId = $isAtom
            ? trim((string) $item->id)
            : trim((string) ($item->guid ?? ''));

        if ($externalId === '') {
            $link = $isAtom
                ? (string) ($item->link['href'] ?? '')
                : (string) ($item->link ?? '');
            $externalId = $link !== '' ? sha1($link) : sha1($title . ($item->pubDate ?? $item->published ?? ''));
        }

        // Date d'evenement : ordre de priorite
        $startsAt = $this->extractDate($item, $isAtom);

        // Description / summary
        $description = $isAtom
            ? trim((string) ($item->summary ?? $item->content ?? ''))
            : trim((string) ($item->description ?? ''));

        // Catégorie source
        $category = $isAtom
            ? trim((string) ($item->category['term'] ?? ''))
            : trim((string) ($item->category ?? ''));

        $url = $isAtom
            ? (string) ($item->link['href'] ?? '')
            : (string) ($item->link ?? '');

        return [
            'external_id' => $externalId,
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'starts_at' => $startsAt,
            'ends_at' => null,    // Les RSS ne contiennent quasi jamais une date de fin
            'venue' => $feedConfig['venue_default'] ?? null,
            'address' => null,
            'latitude' => null,
            'longitude' => null,
            'category' => $category !== '' ? $category : ($feedConfig['category_default'] ?? null),
            'price_info' => null,
            'url' => $url !== '' ? $url : null,
            'raw' => [
                'feed' => $feedSlug,
                'xml' => $item->asXML(),
            ],
        ];
    }

    /**
     * Extrait la date d'evenement reelle (pas la date de publication
     * du flux). Ordre de priorite :
     *   - dc:date (RSS) ou published (Atom) : date precise de l'event
     *   - eventDate (extension custom Theatre Royal)
     *   - pubDate (RSS) ou updated (Atom) : fallback
     */
    protected function extractDate(SimpleXMLElement $item, bool $isAtom): ?string
    {
        // dc:date (namespace Dublin Core, present sur les sites WordPress agenda)
        $namespaces = $item->getNamespaces(true);
        if (isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            if (isset($dc->date) && (string) $dc->date !== '') {
                return $this->parseDateString((string) $dc->date);
            }
        }

        if ($isAtom) {
            if (isset($item->published) && (string) $item->published !== '') {
                return $this->parseDateString((string) $item->published);
            }
            if (isset($item->updated) && (string) $item->updated !== '') {
                return $this->parseDateString((string) $item->updated);
            }
        } else {
            if (isset($item->eventDate) && (string) $item->eventDate !== '') {
                return $this->parseDateString((string) $item->eventDate);
            }
            if (isset($item->pubDate) && (string) $item->pubDate !== '') {
                return $this->parseDateString((string) $item->pubDate);
            }
        }

        return null;
    }

    protected function parseDateString(string $raw): ?string
    {
        try {
            return Carbon::parse($raw)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }
}
