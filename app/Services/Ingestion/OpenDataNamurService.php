<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wrapper API data.namur.be (format Open Data Soft).
 *
 * Mode fixture (BIA_INGEST_FIXTURE_MODE=true) : lit le snapshot local
 *   tests/Fixtures/opendata/namur-agenda-snapshot.json sans appel HTTP.
 *   Active par defaut en local + testing pour ne pas hammer l'API
 *   pendant le dev.
 *
 * Mode HTTP : appelle l'API publique avec User-Agent identifie, timeout
 *   30s. La donnee Open Data Soft est sous CC BY 4.0 (cf. brief §8bis),
 *   attribution affichee par <DataAttribution source="opendata" />.
 *
 * Toutes les exceptions reseau sont attrapees et logguees dans le
 * channel 'ingestion' — pas de plantage du pipeline si la source change
 * de format ou tombe.
 */
class OpenDataNamurService
{
    public const SOURCE = 'opendata_namur';

    /**
     * Recupere les events de l'agenda Namur et les normalise au format Bia.
     *
     * @return array<int, array{external_id:string, title:string, description:?string, starts_at:?string, ends_at:?string, venue:?string, address:?string, latitude:?float, longitude:?float, category:?string, price_info:?string, url:?string, raw:array}>
     */
    public function fetchEvents(): array
    {
        $payload = config('bia.ingestion.fixture_mode')
            ? $this->fetchFromFixture()
            : $this->fetchFromApi();

        if (! is_array($payload) || ! isset($payload['records']) || ! is_array($payload['records'])) {
            Log::channel('ingestion')->warning('opendata.invalid_payload', [
                'has_records' => isset($payload['records']),
            ]);

            return [];
        }

        $records = $payload['records'];

        $events = [];
        foreach ($records as $record) {
            $normalized = $this->normalizeRecord($record);
            if ($normalized !== null) {
                $events[] = $normalized;
            }
        }

        Log::channel('ingestion')->info('opendata.fetched', [
            'count_raw' => count($records),
            'count_normalized' => count($events),
            'mode' => config('bia.ingestion.fixture_mode') ? 'fixture' : 'api',
        ]);

        return $events;
    }

    protected function fetchFromFixture(): array
    {
        $path = base_path('tests/Fixtures/opendata/namur-agenda-snapshot.json');
        if (! is_file($path)) {
            throw new RuntimeException("Fixture OpenData introuvable : {$path}");
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    protected function fetchFromApi(): array
    {
        $url = config('bia.ingestion.opendata_namur_url');
        $timeout = (int) config('bia.ingestion.http_timeout_seconds', 30);
        $userAgent = config('bia.ingestion.user_agent');

        try {
            $response = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout($timeout)
                ->get($url);

            if (! $response->successful()) {
                Log::channel('ingestion')->error('opendata.http_error', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::channel('ingestion')->error('opendata.http_exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            return [];
        }
    }

    /**
     * Mappe un record Open Data Soft → format event Bia.
     * Tolere les champs manquants : retourne null seulement si l'event
     * n'a ni titre ni recordid (donnée inexploitable).
     */
    protected function normalizeRecord(array $record): ?array
    {
        $fields = $record['fields'] ?? [];
        $recordId = $record['recordid'] ?? null;
        $title = $fields['titre'] ?? $fields['title'] ?? null;

        if (empty($recordId) || empty($title)) {
            return null;
        }

        $geo = $fields['geo_point_2d'] ?? null;
        $latitude = is_array($geo) && isset($geo[0]) ? (float) $geo[0] : null;
        $longitude = is_array($geo) && isset($geo[1]) ? (float) $geo[1] : null;

        return [
            'external_id' => (string) $recordId,
            'title' => trim((string) $title),
            'description' => isset($fields['description']) ? trim((string) $fields['description']) : null,
            'starts_at' => $fields['date_debut'] ?? $fields['date_start'] ?? null,
            'ends_at' => $fields['date_fin'] ?? $fields['date_end'] ?? null,
            'venue' => isset($fields['lieu']) ? trim((string) $fields['lieu']) : null,
            'address' => isset($fields['adresse']) ? trim((string) $fields['adresse']) : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'category' => isset($fields['categorie']) ? trim((string) $fields['categorie']) : null,
            'price_info' => isset($fields['prix']) ? trim((string) $fields['prix']) : null,
            'url' => isset($fields['url']) && $fields['url'] !== '' ? (string) $fields['url'] : null,
            'raw' => $record,
        ];
    }
}
