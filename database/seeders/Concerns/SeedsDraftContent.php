<?php

namespace Database\Seeders\Concerns;

use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Insertion de contenu éditorial en brouillon, sans jamais écraser.
 *
 * On ne touche à aucune fiche existante (publiée, retouchée ou supprimée dans
 * Filament) : re-seeder un lot est sans effet sur ce qui existe déjà.
 */
trait SeedsDraftContent
{
    /**
     * @param  list<array<string, mixed>>  $places
     */
    protected function seedPlaces(array $places): void
    {
        $this->assertUniqueSlugs($places, 'lieu');
        $city = $this->namur();

        DB::transaction(function () use ($places, $city) {
            foreach ($places as $data) {
                if (Place::withTrashed()->where('city_id', $city->id)->where('slug', $data['slug'])->exists()) {
                    continue;
                }

                Place::create(array_merge($data, [
                    'city_id' => $city->id,
                    'source' => Place::SOURCE_ADMIN,
                    'status' => Place::STATUS_DRAFT,
                ]));
            }
        });
    }

    /**
     * Chaque story peut porter `place_slug` (nullable). Un slug inconnu lève
     * une exception et annule tout le lot : pas de story orpheline silencieuse.
     *
     * Le lien lieu → story n'est posé que sur un lieu encore en brouillon : un
     * lieu déjà publié ne doit pas afficher une story non relue.
     *
     * @param  list<array<string, mixed>>  $stories
     */
    protected function seedStories(array $stories): void
    {
        $this->assertUniqueSlugs($stories, 'story');
        $city = $this->namur();

        DB::transaction(function () use ($stories, $city) {
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

                if (Story::withTrashed()->where('city_id', $city->id)->where('slug', $data['slug'])->exists()) {
                    continue;
                }

                $story = Story::create(array_merge($data, [
                    'city_id' => $city->id,
                    'place_id' => $place?->id,
                    'ai_generated' => false,
                    'reviewed_by' => null,
                    'status' => Story::STATUS_DRAFT,
                ]));

                if ($place && $place->story_id === null && $place->status === Place::STATUS_DRAFT) {
                    $place->update(['story_id' => $story->id]);
                }
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function assertUniqueSlugs(array $items, string $label): void
    {
        $duplicates = collect($items)->pluck('slug')->duplicates();
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException("Slug en double dans le lot ({$label}) : " . $duplicates->unique()->implode(', '));
        }
    }

    private function namur(): City
    {
        return City::where('slug', 'namur')->firstOrFail();
    }
}
