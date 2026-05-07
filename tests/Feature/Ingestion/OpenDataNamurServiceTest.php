<?php

use App\Services\Ingestion\OpenDataNamurService;

beforeEach(function () {
    config()->set('bia.ingestion.fixture_mode', true);
});

it('reads events from the local fixture in fixture mode', function () {
    $events = (new OpenDataNamurService)->fetchEvents();

    expect($events)->toBeArray()
        ->and(count($events))->toBeGreaterThanOrEqual(10);

    $first = $events[0];
    expect($first)->toHaveKeys([
        'external_id', 'title', 'description', 'starts_at',
        'venue', 'address', 'latitude', 'longitude', 'category',
    ]);
});

it('normalizes Open Data Soft fields to Bia format', function () {
    $events = (new OpenDataNamurService)->fetchEvents();
    $marche = collect($events)->firstWhere('external_id', 'fxt-001-marche-grognon');

    expect($marche)->not->toBeNull()
        ->and($marche['title'])->toBe('Marché du dimanche au Grognon')
        ->and($marche['venue'])->toBe('Le Grognon')
        ->and($marche['latitude'])->toBe(50.4612)
        ->and($marche['longitude'])->toBe(4.8669)
        ->and($marche['category'])->toBe('Marché')
        ->and($marche['price_info'])->toBe('Gratuit');
});

it('skips records without recordid or title', function () {
    $events = (new OpenDataNamurService)->fetchEvents();

    foreach ($events as $event) {
        expect($event['external_id'])->not->toBeEmpty()
            ->and($event['title'])->not->toBeEmpty();
    }
});

it('handles missing geo_point_2d gracefully (latitude=null)', function () {
    $events = (new OpenDataNamurService)->fetchEvents();
    $brocante = collect($events)->firstWhere('external_id', 'fxt-012-no-coords');

    expect($brocante)->not->toBeNull()
        ->and($brocante['latitude'])->toBeNull()
        ->and($brocante['longitude'])->toBeNull();
});
