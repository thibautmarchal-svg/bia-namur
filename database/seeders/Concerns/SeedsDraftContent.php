<?php

namespace Database\Seeders\Concerns;

use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use RuntimeException;

/**
 * Insertion de contenu éditorial en brouillon, sans jamais écraser.
 *
 * Contrairement à updateOrCreate, firstOrCreate laisse intactes les entrées
 * déjà publiées ou retouchées dans Filament : re-seeder en prod est sans risque.
 */
trait SeedsDraftContent
{
    /**
     * @param  list<array<string, mixed>>  $places
     */
    protected function seedPlaces(array $places): void
    {
        $city = $this->namur();

        foreach ($places as $data) {
            Place::firstOrCreate(
                ['city_id' => $city->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'city_id' => $city->id,
                    'source' => Place::SOURCE_ADMIN,
                    'status' => Place::STATUS_DRAFT,
                ]),
            );
        }
    }

    /**
     * Chaque story peut porter `place_slug` (nullable). Un slug inconnu lève
     * une exception : pas de story orpheline silencieuse.
     *
     * @param  list<array<string, mixed>>  $stories
     */
    protected function seedStories(array $stories): void
    {
        $city = $this->namur();

        foreach ($stories as $data) {
            $placeSlug = $data['place_slug'] ?? null;
            unset($data['place_slug']);

            $place = null;
            if ($placeSlug !== null) {
                $place = Place::where('city_id', $city->id)->where('slug', $placeSlug)->first();
                if (! $place) {
                    throw new RuntimeException("Story « {$data['slug']} » : lieu inconnu « {$placeSlug} ».");
                }
            }

            $story = Story::firstOrCreate(
                ['city_id' => $city->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'city_id' => $city->id,
                    'place_id' => $place?->id,
                    'ai_generated' => false,
                    'reviewed_by' => null,
                    'status' => Story::STATUS_DRAFT,
                ]),
            );

            if ($place && $place->story_id === null) {
                $place->update(['story_id' => $story->id]);
            }
        }
    }

    private function namur(): City
    {
        return City::where('slug', 'namur')->firstOrFail();
    }
}
