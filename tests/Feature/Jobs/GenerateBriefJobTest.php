<?php

use App\Jobs\GenerateBriefJob;
use App\Models\AiRun;
use App\Models\Brief;
use App\Models\BriefItem;
use App\Models\City;
use App\Models\Event;
use App\Services\Ai\ClaudeApiService;
use Carbon\Carbon;
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

    // Cree 3 events factices pour la semaine ISO 19 de 2026
    $weekStart = Carbon::now()->setISODate(2026, 19)->startOfWeek();
    foreach (range(1, 3) as $i) {
        Event::create([
            'city_id' => $this->city->id,
            'source' => 'test',
            'external_id' => "test-{$i}",
            'title' => "Evenement test {$i}",
            'description' => 'Description courte de l\'evenement',
            'starts_at' => (clone $weekStart)->addDays($i),
            'venue_name' => 'Venue test',
            'category' => ['test'],
            'status' => Event::STATUS_NORMALIZED,
        ]);
    }
});

it('generates a brief with items from the mock fixture', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);
    $brief = $job->handle(new ClaudeApiService(mockMode: true));

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

    $brief1 = $job->handle(new ClaudeApiService(mockMode: true));
    $brief2 = $job->handle(new ClaudeApiService(mockMode: true));

    expect(Brief::count())->toBe(1)
        ->and($brief1->id)->toBe($brief2->id)
        ->and(BriefItem::count())->toBe(6); // pas double
});

it('records ai_runs trace and links it to the brief polymorphically', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);
    $brief = $job->handle(new ClaudeApiService(mockMode: true));

    $aiRun = AiRun::latest('id')->first();

    expect($aiRun->status)->toBe(AiRun::STATUS_SUCCESS)
        ->and($aiRun->type)->toBe(AiRun::TYPE_BRIEF_WEEKLY)
        ->and($aiRun->related_type)->toBe(Brief::class)
        ->and($aiRun->related_id)->toBe($brief->id);
});

it('stores selected event ids in the brief', function () {
    $job = new GenerateBriefJob('namur', 2026, 19);
    $brief = $job->handle(new ClaudeApiService(mockMode: true));

    expect($brief->selected_event_ids)->toBeArray()
        ->and($brief->selected_event_ids)->toHaveCount(6); // 6 items dans la fixture
});

it('fails clearly when the city does not exist', function () {
    $job = new GenerateBriefJob('mons', 2026, 19);

    expect(fn () => $job->handle(new ClaudeApiService(mockMode: true)))
        ->toThrow(ModelNotFoundException::class);
});
