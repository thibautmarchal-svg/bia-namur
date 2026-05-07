<?php

use App\Services\Ingestion\RssIngestService;

beforeEach(function () {
    config()->set('bia.ingestion.fixture_mode', true);
});

it('parses Le Delta RSS 2.0 feed (4 items)', function () {
    $events = (new RssIngestService)->fetchFromFeed('rss_delta');

    expect($events)->toHaveCount(4);

    $first = $events[0];
    expect($first['title'])->toBe('Sweet Lord Trio — jazz dans la cave')
        ->and($first['venue'])->toBe('Le Delta')   // venue_default si pas dans le RSS
        ->and($first['url'])->toContain('ledelta.be')
        ->and($first['external_id'])->toBe('delta-evt-1024')
        ->and($first['starts_at'])->toContain('2026-05-16');   // dc:date prime sur pubDate
});

it('parses Belvédère Atom feed (3 entries)', function () {
    $events = (new RssIngestService)->fetchFromFeed('rss_belvedere');

    expect($events)->toHaveCount(3);

    $dj = $events[0];
    expect($dj['title'])->toBe('Soiree DJ : techno house')
        ->and($dj['venue'])->toBe('Le Belvédère')
        ->and($dj['url'])->toContain('belvedere-namur.be')
        ->and($dj['starts_at'])->toContain('2026-05-15');
});

it('parses Theatre Royal RSS with custom eventDate field', function () {
    $events = (new RssIngestService)->fetchFromFeed('rss_theatre_royal');

    expect($events)->toHaveCount(3);

    // Le voyage de Bia : eventDate = 2026-05-12, alors que pubDate = 2026-05-02
    $bia = collect($events)->firstWhere('external_id', 'tr-2026-spectacle-jeunesse-bia');
    expect($bia)->not->toBeNull()
        ->and($bia['title'])->toContain('Le voyage de Bia')
        ->and($bia['starts_at'])->toContain('2026-05-12');   // doit prendre eventDate, pas pubDate
});

it('returns empty array if feed slug is unknown', function () {
    expect((new RssIngestService)->fetchFromFeed('rss_unknown'))->toBe([]);
});

it('skips items without a title', function () {
    config()->set('bia.rss_feeds.test_empty', [
        'name' => 'Test',
        'url' => 'https://example.com/feed',
        'venue_default' => 'Test',
        'category_default' => 'test',
        'fixture' => 'nonexistent.xml',
    ]);

    expect((new RssIngestService)->fetchFromFeed('test_empty'))->toBe([]);
});

it('uses category from feed item, falling back to category_default', function () {
    $events = (new RssIngestService)->fetchFromFeed('rss_belvedere');
    $electro = collect($events)->firstWhere('external_id', 'tag:belvedere-namur.be,2026:dj-1023');
    expect($electro['category'])->toBe('electro');
});
