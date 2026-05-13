<?php

use App\Jobs\GenerateBriefJob;
use App\Models\AiRun;
use App\Models\Brief;
use App\Models\BriefItem;
use App\Models\City;
use App\Services\Ai\ClaudeApiService;
use App\Services\Ingestion\NamurAgendaRssService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    config()->set('bia.ai.mock_mode', true);

    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);

    // Fake RSS service qui retourne quelques items factices namurois.
    // On evite tout appel reseau a namur.be dans la suite Pest.
    $this->fakeRss = new class extends NamurAgendaRssService
    {
        public function fetchItems(int $limit = 50): array
        {
            return [
                ['title' => 'Marché du dimanche au Grognon', 'link' => 'https://namur.be/1', 'description' => 'Marché dominical', 'date' => '2026-05-10'],
                ['title' => 'Concert Sweet Lord Trio', 'link' => 'https://namur.be/2', 'description' => 'Jazz au Belvédère', 'date' => '2026-05-11'],
                ['title' => 'Vernissage carnets de voyage', 'link' => 'https://namur.be/3', 'description' => 'Expo MCN', 'date' => '2026-05-12'],
                ['title' => 'Balade nature à la confluence', 'link' => 'https://namur.be/4', 'description' => 'Pont des Ardennes', 'date' => '2026-05-13'],
                ['title' => 'Dégustation asperges', 'link' => 'https://namur.be/5', 'description' => 'Bia Bouquet', 'date' => '2026-05-14'],
            ];
        }
    };
});

it('generates a brief with items from the mock fixture', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);
    $brief = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);

    expect($brief)->toBeInstanceOf(Brief::class)
        ->and($brief->city_id)->toBe($this->city->id)
        ->and($brief->year)->toBe(2026)
        ->and($brief->week_number)->toBe(19)
        ->and($brief->slug)->toBe('2026-W19')
        ->and($brief->status)->toBe(Brief::STATUS_DRAFT_AI)
        ->and($brief->intro_text)->toBeString()
        ->and($brief->intro_text)->not->toBeEmpty();

    $items = $brief->items()->orderBy('position')->get();
    expect($items)->toHaveCount(6)   // fixture brief_mock_v1.json a 6 items
        ->and($items->first()->position)->toBe(1)
        ->and($items->first()->ai_text)->toContain('Marché du dimanche au Grognon')
        ->and($items->first()->reasoning)->toBeArray()
        ->and($items->first()->reasoning['venue'])->toBe('Le Grognon');
});

it('is idempotent : re-runs replace items, not duplicate the brief', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);

    $brief1 = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);
    $brief2 = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);

    expect(Brief::count())->toBe(1)
        ->and($brief1->id)->toBe($brief2->id)
        ->and(BriefItem::count())->toBe(6); // pas double
});

it('restores a soft-deleted brief for the same week instead of failing', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);

    $brief1 = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);
    $brief1->delete();
    expect($brief1->fresh()->trashed())->toBeTrue();

    $brief2 = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);

    expect(Brief::count())->toBe(1)
        ->and($brief1->id)->toBe($brief2->id)
        ->and($brief2->trashed())->toBeFalse();
});

it('records ai_runs trace and links it to the brief polymorphically', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);
    $brief = $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss);

    $aiRun = AiRun::latest('id')->first();

    expect($aiRun->status)->toBe(AiRun::STATUS_SUCCESS)
        ->and($aiRun->type)->toBe(AiRun::TYPE_BRIEF_WEEKLY)
        ->and($aiRun->related_type)->toBe(Brief::class)
        ->and($aiRun->related_id)->toBe($brief->id);
});

it('fails clearly when the city does not exist', function () {
    $job = new GenerateBriefJob('mons', 2026, 19);

    expect(fn () => $job->handle(new ClaudeApiService(mockMode: true), $this->fakeRss))
        ->toThrow(ModelNotFoundException::class);
});
