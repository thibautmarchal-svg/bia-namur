<?php

use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use Database\Seeders\CitySeeder;
use Database\Seeders\Concerns\SeedsDraftContent;
use Database\Seeders\PlaceSeeder;
use Database\Seeders\StorySeeder;
use Illuminate\Database\Seeder;

const FOUNDING_PLACE_SLUGS = [
    'citadelle-de-namur', 'cathedrale-saint-aubain', 'marche-saint-aubain', 'le-delta', 'le-bia-bouquet',
];
const FOUNDING_STORY_SLUGS = ['la-rue-saintraint', 'lorigine-du-bia-bouquet'];

function contentSeeder(): Seeder
{
    return new class extends Seeder
    {
        use SeedsDraftContent;

        public function run(): void {}

        public function places(array $places): void
        {
            $this->seedPlaces($places);
        }

        public function stories(array $stories): void
        {
            $this->seedStories($stories);
        }
    };
}

function samplePlace(array $overrides = []): array
{
    return array_merge([
        'slug' => 'lieu-de-test',
        'name' => 'Lieu de test',
        'type' => 'patrimoine',
        'description' => 'Un lieu pour les tests.',
        'latitude' => 50.4615,
        'longitude' => 4.8635,
        'address' => 'Rue du Test 1, 5000 Namur',
        'neighborhood' => 'Centre',
        'tags' => ['test'],
    ], $overrides);
}

function sampleStory(array $overrides = []): array
{
    return array_merge([
        'slug' => 'story-de-test',
        'type' => Story::TYPE_PATRIMOINE,
        'title' => 'Une story de test',
        'excerpt' => 'Un extrait.',
        'content' => 'Un contenu.',
        'place_slug' => null,
    ], $overrides);
}

/** Classes des lots de contenu qui existent déjà. */
function lotSeeders(): array
{
    return array_values(array_filter([
        'Database\\Seeders\\PlacesLot01Seeder',
        'Database\\Seeders\\StoriesLot01Seeder',
        'Database\\Seeders\\PlacesLot02Seeder',
        'Database\\Seeders\\StoriesLot02Seeder',
    ], 'class_exists'));
}

beforeEach(function () {
    $this->seed(CitySeeder::class);
    $this->namur = City::where('slug', 'namur')->firstOrFail();
});

it('creates new places and stories as draft', function () {
    $seeder = contentSeeder();
    $seeder->places([samplePlace()]);
    $seeder->stories([sampleStory(['place_slug' => 'lieu-de-test'])]);

    $place = Place::where('slug', 'lieu-de-test')->firstOrFail();
    $story = Story::where('slug', 'story-de-test')->firstOrFail();

    expect($place->status)->toBe(Place::STATUS_DRAFT)
        ->and($place->source)->toBe(Place::SOURCE_ADMIN)
        ->and($story->status)->toBe(Story::STATUS_DRAFT)
        ->and($story->ai_generated)->toBeFalse()
        ->and($story->reviewed_by)->toBeNull();
});

it('forces draft even if the data says published', function () {
    contentSeeder()->places([samplePlace(['status' => Place::STATUS_PUBLISHED])]);

    expect(Place::where('slug', 'lieu-de-test')->firstOrFail()->status)->toBe(Place::STATUS_DRAFT);
});

it('links story and place both ways', function () {
    $seeder = contentSeeder();
    $seeder->places([samplePlace()]);
    $seeder->stories([sampleStory(['place_slug' => 'lieu-de-test'])]);

    $place = Place::where('slug', 'lieu-de-test')->firstOrFail();
    $story = Story::where('slug', 'story-de-test')->firstOrFail();

    expect($story->place_id)->toBe($place->id)
        ->and($place->story_id)->toBe($story->id);
});

it('does not overwrite a published place or story on re-seed', function () {
    $seeder = contentSeeder();
    $seeder->places([samplePlace()]);
    $seeder->stories([sampleStory()]);

    Place::where('slug', 'lieu-de-test')->update(['status' => Place::STATUS_PUBLISHED, 'name' => 'Nom retouché']);
    Story::where('slug', 'story-de-test')->update(['status' => Story::STATUS_PUBLISHED]);

    $seeder->places([samplePlace()]);
    $seeder->stories([sampleStory()]);

    $place = Place::where('slug', 'lieu-de-test')->firstOrFail();
    expect($place->status)->toBe(Place::STATUS_PUBLISHED)
        ->and($place->name)->toBe('Nom retouché')
        ->and(Story::where('slug', 'story-de-test')->firstOrFail()->status)->toBe(Story::STATUS_PUBLISHED)
        ->and(Place::where('slug', 'lieu-de-test')->count())->toBe(1);
});

it('throws on an unknown place_slug instead of creating an orphan story', function () {
    expect(fn () => contentSeeder()->stories([sampleStory(['place_slug' => 'nexiste-pas'])]))
        ->toThrow(RuntimeException::class);

    expect(Story::where('slug', 'story-de-test')->exists())->toBeFalse();
});

it('does not attach a draft story to an already published place', function () {
    $seeder = contentSeeder();
    $seeder->places([samplePlace()]);
    Place::where('slug', 'lieu-de-test')->update(['status' => Place::STATUS_PUBLISHED]);

    $seeder->stories([sampleStory(['place_slug' => 'lieu-de-test'])]);

    expect(Place::where('slug', 'lieu-de-test')->firstOrFail()->story_id)->toBeNull();
});

it('throws on duplicate slugs inside one batch', function () {
    expect(fn () => contentSeeder()->places([samplePlace(), samplePlace()]))->toThrow(RuntimeException::class);
    expect(fn () => contentSeeder()->stories([sampleStory(), sampleStory()]))->toThrow(RuntimeException::class);
});

it('rolls back the whole story batch when one story fails', function () {
    expect(fn () => contentSeeder()->stories([
        sampleStory(['slug' => 'story-ok']),
        sampleStory(['slug' => 'story-ko', 'place_slug' => 'nexiste-pas']),
    ]))->toThrow(RuntimeException::class);

    expect(Story::where('slug', 'story-ok')->exists())->toBeFalse();
});

it('does not crash on re-seed when a place was soft-deleted', function () {
    $seeder = contentSeeder();
    $seeder->places([samplePlace()]);
    Place::where('slug', 'lieu-de-test')->firstOrFail()->delete();

    $seeder->places([samplePlace()]);

    expect(Place::withTrashed()->where('slug', 'lieu-de-test')->count())->toBe(1);
});

describe('real content', function () {
    beforeEach(function () {
        $this->seed(PlaceSeeder::class);
        $this->seed(StorySeeder::class);
        foreach (lotSeeders() as $class) {
            $this->seed($class);
        }
    });

    it('has unique slugs per city', function () {
        $places = Place::withTrashed()->pluck('slug');
        $stories = Story::withTrashed()->pluck('slug');

        expect($places->duplicates())->toBeEmpty()
            ->and($stories->duplicates())->toBeEmpty();
    });

    it('keeps every new place and story in draft', function () {
        $newPlaces = Place::whereNotIn('slug', FOUNDING_PLACE_SLUGS)->get();
        $newStories = Story::whereNotIn('slug', FOUNDING_STORY_SLUGS)->get();

        expect($newPlaces->pluck('status')->unique()->all())->each->toBe(Place::STATUS_DRAFT)
            ->and($newStories->pluck('status')->unique()->all())->each->toBe(Story::STATUS_DRAFT);
    });

    it('keeps coordinates in the Namur area', function () {
        foreach (Place::whereNotNull('latitude')->get() as $place) {
            expect($place->longitude)->not->toBeNull($place->slug)
                ->and((float) $place->latitude)->toBeBetween(50.3, 50.6, $place->slug)
                ->and((float) $place->longitude)->toBeBetween(4.6, 5.1, $place->slug);
        }
    });

    it('resolves every story-to-place and place-to-story link', function () {
        foreach (Story::whereNotNull('place_id')->get() as $story) {
            expect(Place::find($story->place_id))->not->toBeNull($story->slug);
        }
        foreach (Place::whereNotNull('story_id')->get() as $place) {
            expect(Story::find($place->story_id))->not->toBeNull($place->slug);
        }
    });

    it('has no empty or placeholder values in opening hours and contact', function () {
        foreach (Place::whereNotIn('slug', FOUNDING_PLACE_SLUGS)->get() as $place) {
            foreach (array_merge($place->opening_hours ?? [], $place->contact ?? []) as $key => $value) {
                expect(trim((string) $value))->not->toBe('', "{$place->slug}.{$key}")
                    ->and(strtolower((string) $value))->not->toContain('inconnu')
                    ->and(strtolower((string) $value))->not->toContain('tbd');
            }
        }
    });

    it('keeps new stories between 200 and 400 words', function () {
        foreach (Story::whereNotIn('slug', FOUNDING_STORY_SLUGS)->get() as $story) {
            expect(str_word_count(strip_tags($story->content), 0, 'àâäéèêëîïôöùûüçÀÉÈ'))
                ->toBeBetween(200, 400, $story->slug);
        }
    });
    it('has no source-commentary left in publishable text', function () {
        $banned = ['les sources', 'certaines sources', 'pages anciennes', "d'un document à l'autre", "je n'ai pas pu"];
        foreach (Story::whereNotIn('slug', FOUNDING_STORY_SLUGS)->get() as $story) {
            foreach ($banned as $phrase) {
                expect(str_contains(mb_strtolower($story->content), $phrase))->toBeFalse("{$story->slug}: « {$phrase} »");
            }
        }
    });

    it('has no unconfirmed opening hours on new places', function () {
        expect(Place::whereNotIn('slug', FOUNDING_PLACE_SLUGS)->whereNotNull('opening_hours')->count())->toBe(0);
    });
});
