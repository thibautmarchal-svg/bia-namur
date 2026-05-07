<?php

use App\Jobs\IngestRssJob;
use App\Models\City;
use App\Models\Event;
use App\Services\Ingestion\RssIngestService;

beforeEach(function () {
    config()->set('bia.ingestion.fixture_mode', true);
    config()->set('bia.sources.rss_delta', true);
    config()->set('bia.sources.rss_belvedere', true);
    config()->set('bia.sources.rss_theatre_royal', true);

    City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);
});

it('ingests events from all 3 RSS feeds', function () {
    $stats = (new IngestRssJob('namur'))->handle(new RssIngestService);

    // Le Delta = 4, Belvédère = 3, Théâtre Royal = 3 → total 10
    expect($stats['inserted'])->toBe(10)
        ->and($stats['updated'])->toBe(0)
        ->and($stats['feeds_active'])->toBe(3)
        ->and(Event::where('source', 'rss_delta')->count())->toBe(4)
        ->and(Event::where('source', 'rss_belvedere')->count())->toBe(3)
        ->and(Event::where('source', 'rss_theatre_royal')->count())->toBe(3);
});

it('skips a feed if its feature flag is disabled', function () {
    config()->set('bia.sources.rss_delta', false);

    $stats = (new IngestRssJob('namur'))->handle(new RssIngestService);

    expect($stats['feeds_active'])->toBe(2)
        ->and(Event::where('source', 'rss_delta')->count())->toBe(0)
        ->and(Event::where('source', 'rss_belvedere')->count())->toBe(3);
});

it('is idempotent : second run updates existing events', function () {
    (new IngestRssJob('namur'))->handle(new RssIngestService);
    $stats2 = (new IngestRssJob('namur'))->handle(new RssIngestService);

    expect($stats2['inserted'])->toBe(0)
        ->and($stats2['updated'])->toBe(10)
        ->and(Event::where('source', 'like', 'rss_%')->count())->toBe(10);
});

it('preserves the feed XML in raw_payload for audit', function () {
    (new IngestRssJob('namur'))->handle(new RssIngestService);

    $delta = Event::where('source', 'rss_delta')->first();
    expect($delta->raw_payload)->toBeArray()
        ->and($delta->raw_payload['feed'])->toBe('rss_delta')
        ->and($delta->raw_payload['xml'])->toContain('<item>');
});

it('uses venue_default from config when item has no venue', function () {
    (new IngestRssJob('namur'))->handle(new RssIngestService);

    $deltaEvent = Event::where('source', 'rss_delta')->first();
    expect($deltaEvent->venue_name)->toBe('Le Delta');

    $belvEvent = Event::where('source', 'rss_belvedere')->first();
    expect($belvEvent->venue_name)->toBe('Le Belvédère');
});
