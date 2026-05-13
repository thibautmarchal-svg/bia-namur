<?php

namespace App\Services\Ingestion;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

/**
 * Fetch + parse le flux RSS officiel de la Ville de Namur.
 *
 * URL : https://www.namur.be/fr/agenda/agenda/RSS
 *
 * C'est la seule source publique fiable que nous avons trouvee a J27 :
 *  - OpenData v2 (dataset namur-agenda-des-evenements) → dernier update 2018
 *  - RSS Le Delta / Belvedere / Theatre Royal → 404
 *
 * Le flux est en RSS 1.0 (RDF) avec namespace Dublin Core. Chaque item
 * contient title / link / description / dc:date (date de publication
 * du fil, PAS la date de l'evenement — la date de l'event est dans
 * le titre ou la description en texte libre, c'est Claude qui se
 * debrouille pour la decoder).
 *
 * Retourne un array brut, pas d'insertion en DB : ce service est concu
 * pour alimenter directement le prompt brief Claude sans passer par la
 * table events (qui restera vide tant qu'on n'a pas de source structuree).
 */
class NamurAgendaRssService
{
    public const DEFAULT_URL = 'https://www.namur.be/fr/agenda/agenda/RSS';

    /**
     * Fetch le flux et retourne les items normalises.
     *
     * @return array<int, array{title:string, link:string, description:string, date:?string}>
     */
    public function fetchItems(int $limit = 50): array
    {
        $url = (string) config('bia.ingestion.namur_agenda_rss_url', self::DEFAULT_URL);

        try {
            $response = Http::withHeaders([
                'User-Agent' => (string) config('bia.ingestion.user_agent', 'BiaNamurBot/1.0 (+contact@bianamur.be)'),
                'Accept' => 'application/rss+xml, application/xml, text/xml',
            ])
                ->withOptions(['verify' => false])
                ->timeout((int) config('bia.ingestion.http_timeout_seconds', 30))
                ->get($url);
        } catch (Throwable $e) {
            Log::channel('ingestion')->error('namur_agenda_rss.http_exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            Log::channel('ingestion')->warning('namur_agenda_rss.http_error', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return [];
        }

        $xmlString = $response->body();
        if ($xmlString === '') {
            return [];
        }

        try {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($xmlString);
        } catch (Throwable $e) {
            Log::channel('ingestion')->error('namur_agenda_rss.parse_error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $namespaces = $xml->getNamespaces(true);

        // RSS 1.0 (RDF) : items directement enfants de la racine
        // RSS 2.0 classique : items enfants de <channel>
        $items = $xml->item ?? $xml->channel->item ?? [];

        $parsed = [];
        foreach ($items as $item) {
            $dc = isset($namespaces['dc']) ? $item->children($namespaces['dc']) : null;

            $title = trim((string) $item->title);
            if ($title === '') {
                continue;
            }

            $parsed[] = [
                'title' => $title,
                'link' => trim((string) $item->link),
                'description' => trim((string) $item->description),
                'date' => $dc ? trim((string) $dc->date) : null,
            ];
        }

        Log::channel('ingestion')->info('namur_agenda_rss.fetched', [
            'url' => $url,
            'count' => count($parsed),
        ]);

        return array_slice($parsed, 0, $limit);
    }
}
