<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocode une adresse via Nominatim OSM (gratuit, CC BY-SA).
 *
 * Conditions d'usage Nominatim :
 *  - User-Agent identifie obligatoire (cf. config bia.ingestion.user_agent)
 *  - Rate limit strict 1 req/s (cf. nominatim_rate_limit_ms = 1100ms par
 *    securite). Coordonne globalement via cache key 'nominatim:last_call'.
 *  - Cache des resultats par hash d'adresse pour eviter les re-requests
 *    (cle 'geocode:{sha1(adresse)}', ttl 30 jours).
 *
 * Mode fixture : pas d'appel reseau, retourne null. Les events de la
 *   fixture OpenData ont deja leurs coords (geo_point_2d), donc le
 *   geocoding n'est pas necessaire en dev.
 */
class GeocodingService
{
    /**
     * Geocode une adresse complete et retourne ['lat' => float, 'lng' => float] ou null.
     */
    public function geocode(?string $address): ?array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        $cacheKey = 'geocode:' . sha1(mb_strtolower($address));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 'NEGATIVE_HIT' ? null : $cached;
        }

        // En mode fixture : pas d'appel reseau, on memorise un negative hit
        // courte duree pour ne pas re-tenter en boucle.
        if (config('bia.ingestion.fixture_mode')) {
            Cache::put($cacheKey, 'NEGATIVE_HIT', now()->addHour());

            return null;
        }

        $this->respectRateLimit();

        $userAgent = config('bia.ingestion.user_agent');
        $url = config('bia.ingestion.nominatim_url');
        $timeout = (int) config('bia.ingestion.http_timeout_seconds', 30);

        try {
            $response = Http::withHeaders(['User-Agent' => $userAgent])
                ->timeout($timeout)
                ->get($url, [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 0,
                    'countrycodes' => 'be',
                ]);

            if (! $response->successful()) {
                Log::channel('ingestion')->warning('nominatim.http_error', [
                    'status' => $response->status(),
                    'address_hash' => sha1($address),
                ]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data) || ! isset($data[0]['lat'], $data[0]['lon'])) {
                Cache::put($cacheKey, 'NEGATIVE_HIT', now()->addDays(7));

                return null;
            }

            $coords = [
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon'],
            ];

            Cache::put($cacheKey, $coords, now()->addDays(30));

            return $coords;
        } catch (\Throwable $e) {
            Log::channel('ingestion')->error('nominatim.exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            return null;
        }
    }

    /**
     * Respecte le rate limit Nominatim 1 req/s en bloquant via cache.
     * Si le dernier call etait il y a < 1100ms, on attend.
     */
    protected function respectRateLimit(): void
    {
        $minDelayMs = (int) config('bia.ingestion.nominatim_rate_limit_ms', 1100);
        $cacheKey = 'nominatim:last_call_at';

        $lastCall = Cache::get($cacheKey);
        if ($lastCall !== null) {
            $elapsedMs = (microtime(true) - $lastCall) * 1000;
            if ($elapsedMs < $minDelayMs) {
                usleep((int) (($minDelayMs - $elapsedMs) * 1000));
            }
        }

        Cache::put($cacheKey, microtime(true), now()->addMinutes(5));
    }
}
