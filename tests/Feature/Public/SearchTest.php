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

it('search: renders empty state without query', function () {
    $this->get('/recherche')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Search/Index')
            ->where('query', '')
            ->where('results.total', 0),
        );
});

it('search: ignores queries shorter than minLength', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-delta',
        'name' => 'Le Delta',
        'type' => 'culture',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/recherche?q=L')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('results.total', 0));
});

it('search: finds a place by name', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-delta',
        'name' => 'Le Delta',
        'type' => 'culture',
        'description' => 'Salle de concert.',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/recherche?q=delta')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('query', 'delta')
            ->has('results.places', 1)
            ->where('results.places.0.slug', 'le-delta')
            ->where('results.total', 1),
        );
});

it('search: finds a place by neighborhood', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'cafe-jambois',
        'name' => 'Café Jambois',
        'type' => 'cafe',
        'neighborhood' => 'Jambes',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/recherche?q=jambes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('results.places', 1));
});

it('search: skips draft places', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'secret',
        'name' => 'Secret café',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_DRAFT,
    ]);

    $this->get('/recherche?q=secret')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('results.total', 0));
});

it('search: finds story by content', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'le-bia-bouquet',
        'title' => 'Le Bia Bouquet',
        'type' => Story::TYPE_TRADITION,
        'content' => 'La tradition wallonne du Bia Bouquet remonte au XIXe siècle.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $this->get('/recherche?q=wallonne')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('results.stories', 1)
            ->where('results.stories.0.slug', 'le-bia-bouquet'),
        );
});

it('search: finds brief by title', function () {
    Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Cette semaine à Namur',
        'intro_text' => 'Première semaine.',
        'status' => Brief::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $this->get('/recherche?q=semaine')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('results.briefs', 1));
});

it('search: name match ranks before description match', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'side',
        'name' => 'Side café',
        'type' => 'cafe',
        'description' => 'On y parle de pekèt.',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'pekèt-bar',
        'name' => 'Pekèt Bar',
        'type' => 'bar',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/recherche?q=pekèt')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('results.places.0.slug', 'pekèt-bar'),
        );
});

it('search: escapes SQL LIKE wildcards', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'safe',
        'name' => 'Safe place',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    // % devrait être échappé et ne plus matcher tout le monde
    $this->get('/recherche?q=%')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('results.total', 0));
});
