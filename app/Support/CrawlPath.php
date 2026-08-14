<?php

namespace App\Support;

use App\Models\Brief;
use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Support\Facades\Cache;

/**
 * Plan de liens crawlable injecte en <noscript> dans app.blade.php.
 *
 * Contexte SEO : l'app est rendue par Inertia cote client, donc le HTML
 * servi ne contient aucun <a href>. Googlebot doit executer le JS pour
 * decouvrir les briefs/lieux/stories, ce qui passe par la file de rendu
 * differee — fatal pour un domaine neuf sans autorite (cf. GSC
 * "Aucune page d'origine detectee" sur /brief/{slug}).
 *
 * Ce plan donne un chemin de decouverte immediat, sans SSR Node.
 * Cache 1h, invalide implicitement a chaque publication (TTL court).
 */
class CrawlPath
{
    private const CACHE_KEY = 'seo.crawl_path.namur';

    private const CACHE_TTL = 3600;

    /**
     * @return list<array{url:string, label:string}>
     */
    public static function links(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $city = City::where('slug', 'namur')->first();

            if (! $city) {
                return [];
            }

            $links = [
                ['url' => url('/'), 'label' => 'Bia Namur — accueil'],
                ['url' => url('/briefs'), 'label' => 'Tous les briefs hebdo'],
                ['url' => url('/lieux'), 'label' => 'Tous les lieux'],
                ['url' => url('/stories'), 'label' => 'Toutes les stories'],
                ['url' => url('/carte'), 'label' => 'La carte sentimentale'],
                ['url' => url('/wallon'), 'label' => 'Le wallon namurois'],
                ['url' => url('/a-propos'), 'label' => 'A propos de Bia Namur'],
            ];

            $briefs = Brief::query()
                ->forCity($city)
                ->published()
                ->orderByDesc('published_at')
                ->limit(52)
                ->get(['slug', 'title']);

            foreach ($briefs as $brief) {
                $links[] = [
                    'url' => url('/brief/' . $brief->slug),
                    'label' => $brief->title ?: 'Brief ' . $brief->slug,
                ];
            }

            $places = Place::query()
                ->forCity($city)
                ->published()
                ->orderBy('name')
                ->get(['slug', 'name']);

            foreach ($places as $place) {
                $links[] = [
                    'url' => url('/lieu/' . $place->slug),
                    'label' => $place->name,
                ];
            }

            $stories = Story::query()
                ->forCity($city)
                ->published()
                ->orderBy('title')
                ->get(['slug', 'title']);

            foreach ($stories as $story) {
                $links[] = [
                    'url' => url('/story/' . $story->slug),
                    'label' => $story->title,
                ];
            }

            return $links;
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
