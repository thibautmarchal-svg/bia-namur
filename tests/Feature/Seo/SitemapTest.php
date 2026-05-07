<?php

use App\Models\Brief;
use App\Models\City;
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
});

it('sitemap returns valid XML', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $xml = $response->getContent();
    expect($xml)
        ->toStartWith('<?xml')
        ->toContain('<urlset')
        ->toContain('</urlset>');
});

it('sitemap includes static editorial pages', function () {
    $response = $this->get('/sitemap.xml');
    $xml = $response->getContent();

    expect($xml)
        ->toContain('/lieux')
        ->toContain('/stories')
        ->toContain('/briefs')
        ->toContain('/carte')
        ->toContain('/wallon')
        ->toContain('/a-propos');
});

it('sitemap excludes admin and auth pages', function () {
    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)
        ->not->toContain('/admin')
        ->not->toContain('/login')
        ->not->toContain('/recherche')
        ->not->toContain('/mes-favoris');
});

it('sitemap includes published places only', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'public-place',
        'name' => 'Public',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'draft-place',
        'name' => 'Draft',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_DRAFT,
    ]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)
        ->toContain('/lieu/public-place')
        ->not->toContain('/lieu/draft-place');
});

it('sitemap includes published stories with lastmod', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'la-rue',
        'title' => 'La rue',
        'type' => Story::TYPE_PATRIMOINE,
        'content' => 'Histoire.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $xml = $this->get('/sitemap.xml')->getContent();

    expect($xml)
        ->toContain('/story/la-rue')
        ->toContain('<lastmod>');
});

it('sitemap caps briefs to last 52 weeks', function () {
    foreach (range(1, 60) as $i) {
        Brief::create([
            'city_id' => $this->city->id,
            'year' => 2025,
            'week_number' => $i,
            'slug' => "2025-W$i",
            'title' => "Brief $i",
            'status' => Brief::STATUS_PUBLISHED,
            'published_at' => now()->subWeeks($i),
        ]);
    }

    $xml = $this->get('/sitemap.xml')->getContent();
    $count = substr_count($xml, '/brief/2025-W');

    expect($count)->toBeLessThanOrEqual(52);
});

it('sitemap escapes XML special chars', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'cafe-amp-co',
        'name' => 'Café & Co',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $xml = $this->get('/sitemap.xml')->getContent();

    // L'URL ne doit PAS contenir de & non echappe
    expect($xml)->not->toMatch('/&(?!amp;|lt;|gt;|quot;|#)/');
});

it('robots.txt file exists in public/ with expected directives', function () {
    // Note : sert via static file en prod, donc bypass de Laravel routing.
    // On valide juste que le contenu sur disque a les bonnes regles.
    $path = public_path('robots.txt');
    expect(file_exists($path))->toBeTrue();

    $body = file_get_contents($path);
    expect($body)
        ->toContain('Sitemap:')
        ->toContain('Disallow: /admin')
        ->toContain('GPTBot')
        ->toContain('SemrushBot');
});
