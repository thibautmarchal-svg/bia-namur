<?php

namespace App\Support;

use App\Models\Brief;
use App\Models\Place;
use App\Models\Story;

/**
 * Genere le JSON-LD schema.org pour les pages publiques.
 *
 * Pourquoi cote serveur : Google rend le JS, mais une payload deterministe
 * cote serveur est plus rapide a indexer et plus simple a tester.
 *
 * Schemas utilises :
 *   - Place fiche → LocalBusiness ou TouristAttraction selon type
 *   - Story → Article
 *   - Brief → Article (NewsArticle si on voulait, mais on ne veut pas
 *     se faire indexer dans Google News, le brief est editorial pas presse)
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
            'url' => url('/lieu/'.$place->slug),
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
            'url' => url('/story/'.$story->slug),
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
            'url' => url('/brief/'.$brief->slug),
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

    private static function publisher(): array
    {
        return [
            '@type' => 'Organization',
            'name' => 'Bia Namur',
            'url' => url('/'),
        ];
    }
}
