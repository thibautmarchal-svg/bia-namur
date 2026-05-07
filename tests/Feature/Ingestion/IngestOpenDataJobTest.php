<?php

use App\Jobs\IngestOpenDataJob;
use App\Models\City;
use App\Models\Event;
use App\Services\Ingestion\OpenDataNamurService;

beforeEach(function () {
    config()->set('bia.ingestion.fixture_mode', true);
    config()->set('bia.sources.opendata_namur', true);

    City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);
});

it('inserts all fixture events with status=ingested on first run', function () {
    $stats = (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);

    expect($stats['inserted'])->toBe(12)
        ->and($stats['updated'])->toBe(0)
        ->and(Event::where('source', 'opendata_namur')->count())->toBe(12)
        ->and(Event::where('status', Event::STATUS_INGESTED)->count())->toBe(12);
});

it('is idempotent: second run updates instead of duplicating', function () {
    (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);
    $statsRerun = (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);

    expect($statsRerun['inserted'])->toBe(0)
        ->and($statsRerun['updated'])->toBe(12)
        ->and(Event::where('source', 'opendata_namur')->count())->toBe(12);
});

it('respects the feature flag config(bia.sources.opendata_namur)=false', function () {
    config()->set('bia.sources.opendata_namur', false);

    $stats = (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);

    expect($stats['inserted'])->toBe(0)
        ->and(Event::count())->toBe(0);
});

it('preserves Open Data raw_payload for traceability', function () {
    (new IngestOpenDataJob('namur'))->handle(new OpenDataNamurService);

    $event = Event::where('external_id', 'fxt-001-marche-grognon')->first();
    expect($event->raw_payload)->toBeArray()
        ->and($event->raw_payload['datasetid'])->toBe('namur-agenda-des-evenements');
});
