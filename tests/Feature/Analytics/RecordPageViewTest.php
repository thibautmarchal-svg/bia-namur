<?php

use App\Models\Brief;
use App\Models\City;
use App\Models\PageView;
use App\Models\Place;
use App\Models\Story;

beforeEach(function () {
    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);

    $this->place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-delta',
        'name' => 'Le Delta',
        'type' => 'culture',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->story = Story::create([
        'city_id' => $this->city->id,
        'slug' => 'le-bia-bouquet',
        'title' => 'Le Bia Bouquet',
        'type' => Story::TYPE_TRADITION,
        'content' => 'Histoire.',
        'status' => Story::STATUS_PUBLISHED,
    ]);
});

it('records a page view on /lieu/{slug}', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0 Chrome/120')
        ->get('/lieu/le-delta')
        ->assertOk();

    expect(PageView::count())->toBe(1);

    $view = PageView::first();
    expect($view->viewable_type)->toBe(Place::class)
        ->and($view->viewable_id)->toBe($this->place->id)
        ->and($view->slug)->toBe('le-delta')
        ->and($view->is_bot)->toBeFalse()
        ->and($view->ip_hash)->toHaveLength(64);
});

it('records a page view on /story/{slug}', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/story/le-bia-bouquet')
        ->assertOk();

    expect(PageView::where('viewable_type', Story::class)->count())->toBe(1);
});

it('records a page view on /brief/{slug}', function () {
    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Brief',
        'status' => Brief::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/brief/2026-W19')
        ->assertOk();

    expect(PageView::where('viewable_type', Brief::class)->count())->toBe(1);
});

it('does not record on listing pages', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/lieux')->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/stories')->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/')->assertOk();

    expect(PageView::count())->toBe(0);
});

it('flags bots via user-agent', function () {
    $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')
        ->get('/lieu/le-delta')
        ->assertOk();

    expect(PageView::where('is_bot', true)->count())->toBe(1);
});

it('skips empty user-agent (treated as bot)', function () {
    $this->withHeader('User-Agent', '')->get('/lieu/le-delta')->assertOk();

    $view = PageView::first();
    expect($view)->not->toBeNull()
        ->and($view->is_bot)->toBeTrue();
});

it('dedupes same ip_hash within 24h window', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/lieu/le-delta')
        ->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/lieu/le-delta')
        ->assertOk();
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/lieu/le-delta')
        ->assertOk();

    expect(PageView::count())->toBe(1);
});

it('records again after dedup window', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/lieu/le-delta')->assertOk();

    // Backdate la 1ere vue de 25h pour simuler un retour le lendemain
    PageView::query()->update(['viewed_at' => now()->subHours(25)]);

    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/lieu/le-delta')->assertOk();

    expect(PageView::count())->toBe(2);
});

it('captures referrer host (external) and ignores self-referrals', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->withHeader('Referer', 'https://twitter.com/some/post')
        ->get('/lieu/le-delta')
        ->assertOk();

    $view = PageView::first();
    expect($view->referrer_host)->toBe('twitter.com');
});

it('does not record on 404 responses', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')
        ->get('/lieu/does-not-exist')
        ->assertNotFound();

    expect(PageView::count())->toBe(0);
});

it('hashes ip with app key salt (no plaintext IP stored)', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/lieu/le-delta')->assertOk();

    $view = PageView::first();
    expect($view->ip_hash)->toHaveLength(64)
        ->and($view->getAttributes())->not->toHaveKey('ip');
});
