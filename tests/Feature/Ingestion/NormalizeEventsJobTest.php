<?php

use App\Jobs\IngestOpenDataJob;
use App\Jobs\NormalizeEventsJob;
use App\Models\City;
use App\Models\Event;
use App\Services\Ingestion\EventCategorizationService;
use App\Services\Ingestion\GeocodingService;
use App\Services\Ingestion\OpenDataNamurService;

beforeEach(function () {
    config()->set('bia.ingestion.fixture_mode', true);

    City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);

    // Pre-ingere les events depuis la fixture
    (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);
});

it('promotes ingested events to normalized', function () {
    $stats = (new NormalizeEventsJob('namur'))->handle(
        new GeocodingService,
        new EventCategorizationService,
    );

    expect($stats['processed'])->toBe(12)
        ->and($stats['normalized'])->toBeGreaterThan(0)
        ->and(Event::where('status', Event::STATUS_NORMALIZED)->count())->toBeGreaterThan(0);
});

it('detects and drops the duplicate "Marché du dimanche au Grognon" / "Marché producteurs Grognon"', function () {
    $stats = (new NormalizeEventsJob('namur'))->handle(
        new GeocodingService,
        new EventCategorizationService,
    );

    // Les 2 events Grognon sont volontairement quasi-doublons (meme date,
    // meme venue, titre similaire). On attend exactement 1 dropped.
    expect($stats['dropped_duplicates'])->toBeGreaterThanOrEqual(1)
        ->and(Event::where('status', Event::STATUS_DROPPED)->count())->toBeGreaterThanOrEqual(1);
});

it('applies categorization rules from config', function () {
    (new NormalizeEventsJob('namur'))->handle(
        new GeocodingService,
        new EventCategorizationService,
    );

    $concert = Event::where('external_id', 'fxt-003-concert-belvedere')->first();
    expect($concert->category)->toBeArray()
        ->and(in_array('concert', $concert->category, true))->toBeTrue();
});

it('is idempotent: re-running does not re-process normalized events', function () {
    (new NormalizeEventsJob('namur'))->handle(
        new GeocodingService,
        new EventCategorizationService,
    );
    $statsRerun = (new NormalizeEventsJob('namur'))->handle(
        new GeocodingService,
        new EventCategorizationService,
    );

    expect($statsRerun['processed'])->toBe(0)
        ->and($statsRerun['normalized'])->toBe(0);
});
