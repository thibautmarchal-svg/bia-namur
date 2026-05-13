<?php

namespace App\Support;

use App\Models\Brief;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Support\Str;

/**
 * Genere le JSON-LD schema.org pour les pages publiques.
 *
 * Pourquoi cote serveur : Google rend le JS, mais une payload deterministe
 * cote serveur (rendue dans le Blade) est instantanement indexable, alors
 * qu'un script injecte par Vue apres hydration peut etre rate par certains
 * crawlers ou pris en compte avec delai.
 *
 * Schemas utilises :
 *   - Home → WebSite + SearchAction (search box dans SERP Google) + Organization
 *   - Place fiche → LocalBusiness ou TouristAttraction selon type + Breadcrumb
 *   - Story → Article + Breadcrumb
 *   - Brief → Article + Breadcrumb (NewsArticle volontairement evite — on ne
 *     veut pas etre indexe dans Google News, c'est editorial pas presse)
 */
class JsonLdBuilder
{
    private const TYPE_TO_SCHEMA = [
        'cafe' => 'CafeOrCoffeeShop',
        'restaurant' => 'Restaurant',
        'bar' => 'BarOrPub',
        'boulangerie' => 'Bakery',
        'librairie' => 'BookStore',
        'patrimoine' => 'TouristAttraction',
        'parc' => 'Park',
        'marche' => 'Place',
        'culture' => 'PerformingArtsTheater',
        'hidden_gem' => 'TouristAttraction',
    ];

    public static function forPlace(Place $place): array
    {
        $schemaType = self::TYPE_TO_SCHEMA[$place->type] ?? 'Place';

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $place->name,
            'url' => url('/lieu/' . $place->slug),
        ];

        if ($place->description) {
            $payload['description'] = $place->description;
        }

        if ($place->latitude !== null && $place->longitude !== null) {
            $payload['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $place->latitude,
                'longitude' => (float) $place->longitude,
            ];
        }

        if ($place->address || $place->neighborhood) {
            $payload['address'] = array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $place->address,
                'addressLocality' => $place->neighborhood ?? 'Namur',
                'addressRegion' => 'Namur',
                'addressCountry' => 'BE',
            ]);
        }

        $contact = $place->contact ?? [];
        if (! empty($contact['phone'])) {
            $payload['telephone'] = $contact['phone'];
        }
        if (! empty($contact['website'])) {
            $payload['sameAs'] = [$contact['website']];
        }

        return $payload;
    }

    public static function forStory(Story $story): array
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $story->title,
            'url' => url('/story/' . $story->slug),
            'inLanguage' => 'fr-BE',
            'publisher' => self::publisher(),
        ];

        if ($story->excerpt) {
            $payload['description'] = $story->excerpt;
        }

        if ($story->updated_at) {
            $payload['dateModified'] = $story->updated_at->toIso8601String();
        }

        if ($story->place) {
            $payload['contentLocation'] = [
                '@type' => 'Place',
                'name' => $story->place->name,
            ];
        }

        return $payload;
    }

    public static function forBrief(Brief $brief): array
    {
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $brief->title,
            'url' => url('/brief/' . $brief->slug),
            'inLanguage' => 'fr-BE',
            'publisher' => self::publisher(),
        ];

        if ($brief->intro_text) {
            $payload['description'] = $brief->intro_text;
        }

        if ($brief->published_at) {
            $payload['datePublished'] = $brief->published_at->toIso8601String();
        }

        return $payload;
    }

    /**
     * Schema home : WebSite (avec SearchAction = potential search box dans
     * les SERP Google) + Organization (logo, identite de marque).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forHome(): array
    {
        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Bia Namur',
                'url' => url('/'),
                'description' => 'Le carnet vivant des Namurois — brief hebdo curaté, carte sentimentale, stories du patrimoine.',
                'inLanguage' => 'fr-BE',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => url('/recherche?q={search_term_string}'),
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            self::organization(),
        ];
    }

    /**
     * Breadcrumb list a injecter sur toutes les pages internes pour aider
     * Google a comprendre la hierarchie + s'afficher dans les SERP.
     *
     * @param  array<int, array{name:string, url:string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $idx) => [
                    '@type' => 'ListItem',
                    'position' => $idx + 1,
                    'name' => $item['name'],
                    'item' => Str::startsWith($item['url'], ['http://', 'https://'])
                        ? $item['url']
                        : url($item['url']),
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /**
     * Organization globale (utilisee comme publisher des Article + standalone
     * sur la home pour l'identite de marque). Inclut sameAs des reseaux
     * sociaux quand ils existent (a completer dans config/bia.php).
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $sameAs = array_filter([
            config('bia.organization.facebook'),
            config('bia.organization.instagram'),
            config('bia.organization.twitter'),
            config('bia.organization.linkedin'),
        ]);

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Bia Namur',
            'url' => url('/'),
            'logo' => url('/images/og/bia-namur-default.png'),
        ];

        if (! empty($sameAs)) {
            $payload['sameAs'] = array_values($sameAs);
        }

        return $payload;
    }

    private static function publisher(): array
    {
        return [
            '@type' => 'Organization',
            'name' => 'Bia Namur',
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url('/images/og/bia-namur-default.png'),
            ],
        ];
    }
}
