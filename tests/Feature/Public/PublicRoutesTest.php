<?php

use App\Models\Brief;
use App\Models\BriefItem;
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

it('home: renders the page even without any content', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('brief')
            ->has('highlightPlaces')
            ->has('latestStories'),
        );
});

it('home: shows the latest published brief', function () {
    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Cette semaine à Namur',
        'intro_text' => 'Première semaine de mai.',
        'status' => Brief::STATUS_PUBLISHED,
        'generated_at' => now(),
        'published_at' => now(),
    ]);

    BriefItem::create([
        'brief_id' => $brief->id,
        'position' => 1,
        'ai_text' => '**Test event**',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home')
            ->where('brief.year', 2026)
            ->where('brief.week_number', 19)
            ->where('brief.title', 'Cette semaine à Namur')
            ->has('brief.cover_photo')
            ->has('brief.items', 1),
        );
});

it('home: surfaces 3 highlight places (newest first)', function () {
    foreach (['citadelle', 'cathedrale', 'le-delta', 'marche'] as $i => $slug) {
        Place::create([
            'city_id' => $this->city->id,
            'slug' => $slug,
            'name' => ucfirst($slug),
            'type' => 'patrimoine',
            'description' => 'Test description.',
            'source' => Place::SOURCE_ADMIN,
            'status' => Place::STATUS_PUBLISHED,
            'updated_at' => now()->subHours(4 - $i),
        ]);
    }

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('highlightPlaces', 3));
});

it('briefs index: lists published briefs in dev tolerant mode', function () {
    Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 18,
        'slug' => '2026-W18',
        'title' => 'Brief 18',
        'status' => Brief::STATUS_PUBLISHED,
    ]);

    $this->get('/briefs')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Briefs/Index')
            ->has('briefs', 1)
            ->where('briefs.0.slug', '2026-W18'),
        );
});

it('brief show: returns 404 when brief does not exist', function () {
    $this->get('/brief/2099-W01')->assertNotFound();
});

it('brief show: renders existing brief with items', function () {
    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 20,
        'slug' => '2026-W20',
        'title' => 'Brief 20',
        'intro_text' => 'Intro 20',
        'status' => Brief::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);
    BriefItem::create(['brief_id' => $brief->id, 'position' => 1, 'ai_text' => 'item un']);
    BriefItem::create(['brief_id' => $brief->id, 'position' => 2, 'ai_text' => 'item deux']);

    $this->get('/brief/2026-W20')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Briefs/Show')
            ->where('brief.slug', '2026-W20')
            ->has('brief.items', 2)
            ->has('brief.cover_photo'),
        );
});

it('places index: lists only published places', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-delta',
        'name' => 'Le Delta',
        'type' => 'culture',
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

    $this->get('/lieux')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Places/Index')
            ->has('places', 1)
            ->where('places.0.slug', 'le-delta'),
        );
});

it('place show: returns 404 for draft places', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'hidden',
        'name' => 'Hidden',
        'type' => 'bar',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_DRAFT,
    ]);

    $this->get('/lieu/hidden')->assertNotFound();
});

it('place show: renders published place with cover photo (default Wikimedia)', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'citadelle-de-namur',  // mappe dans config/bia-photos.php
        'name' => 'Citadelle de Namur',
        'type' => 'patrimoine',
        'description' => 'La forteresse.',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/lieu/citadelle-de-namur')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Places/Show')
            ->where('place.name', 'Citadelle de Namur')
            ->where('place.cover_photo.credit', 'Anoel')
            ->where('place.cover_photo.license', 'CC BY 2.0')
            ->where('place.cover_photo.is_override', false),
        );
});

it('stories index: lists published stories', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'la-rue-saintraint',
        'title' => 'La rue Saintraint',
        'type' => Story::TYPE_PATRIMOINE,
        'content' => 'Lorem 200 mots de patrimoine.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $this->get('/stories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stories/Index')
            ->has('stories', 1)
            ->where('stories.0.slug', 'la-rue-saintraint'),
        );
});

it('story show: renders with cover photo from defaults mapping', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'la-rue-saintraint',
        'title' => 'Saintraint',
        'type' => Story::TYPE_PATRIMOINE,
        'content' => 'Histoire de la rue.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $this->get('/story/la-rue-saintraint')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stories/Show')
            ->where('story.slug', 'la-rue-saintraint')
            ->where('story.cover_photo.credit', 'DasaBezak')
            ->where('story.cover_photo.license', 'CC BY-SA 3.0'),
        );
});

it('map: returns city center + bounding box + georeferenced places', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'with-coords',
        'name' => 'With coords',
        'type' => 'patrimoine',
        'latitude' => 50.4615,
        'longitude' => 4.8635,
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'no-coords',
        'name' => 'No coords',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/carte')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Map')
            ->where('city.slug', 'namur')
            ->where('city.center.lat', 50.4674)
            ->has('places', 1) // seul le place avec coords est expose
            ->where('places.0.slug', 'with-coords'),
        );
});

it('json resources are not wrapped in {data:...}', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'unwrapped',
        'name' => 'Unwrapped test',
        'type' => 'patrimoine',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $this->get('/lieu/unwrapped')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Places/Show')
            ->where('place.name', 'Unwrapped test')
            ->missing('place.data'),
        );
});
