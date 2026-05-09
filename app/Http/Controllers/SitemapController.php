<?php

namespace App\Http\Controllers;

use App\Models\Brief;
use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Http\Response;

/**
 * Sitemap XML genere a la volee pour Google/Bing/DuckDuckGo.
 *
 * Inclut : pages statiques editoriales, lieux publies, stories publiees,
 * briefs publies. Cache 1h cote app + headers HTTP cache adaptes.
 *
 * Format : sitemaps.org schema 0.9 (loc, lastmod, changefreq, priority).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $namur = City::where('slug', 'namur')->firstOrFail();

        $urls = $this->buildUrls($namur);

        $xml = $this->renderXml($urls);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * @return list<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function buildUrls(City $city): array
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [];

        // Pages editoriales statiques
        $static = [
            ['/', 'daily', '1.0'],
            ['/lieux', 'weekly', '0.9'],
            ['/stories', 'weekly', '0.9'],
            ['/briefs', 'weekly', '0.9'],
            ['/carte', 'weekly', '0.7'],
            ['/wallon', 'monthly', '0.6'],
            ['/a-propos', 'yearly', '0.5'],
            ['/contribuer', 'monthly', '0.5'],
        ];
        foreach ($static as [$path, $changefreq, $priority]) {
            $urls[] = [
                'loc' => $base . $path,
                'lastmod' => null,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        }

        // Lieux publies
        $places = Place::query()
            ->forCity($city)
            ->published()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);
        foreach ($places as $place) {
            $urls[] = [
                'loc' => $base . '/lieu/' . $place->slug,
                'lastmod' => $place->updated_at?->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
        }

        // Stories publiees
        $stories = Story::query()
            ->forCity($city)
            ->published()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);
        foreach ($stories as $story) {
            $urls[] = [
                'loc' => $base . '/story/' . $story->slug,
                'lastmod' => $story->updated_at?->toIso8601String(),
                'changefreq' => 'yearly',
                'priority' => '0.7',
            ];
        }

        // Briefs publies (priorite plus basse car contenu hebdo qui se demode)
        $briefs = Brief::query()
            ->forCity($city)
            ->published()
            ->orderByDesc('published_at')
            ->limit(52)   // dernier an seulement
            ->get(['slug', 'published_at', 'updated_at']);
        foreach ($briefs as $brief) {
            $urls[] = [
                'loc' => $base . '/brief/' . $brief->slug,
                'lastmod' => ($brief->published_at ?? $brief->updated_at)?->toIso8601String(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }

    private function renderXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc>' . "\n";
            if ($url['lastmod']) {
                $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1) . '</lastmod>' . "\n";
            }
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }
}
