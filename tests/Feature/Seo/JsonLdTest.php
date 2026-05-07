<?php

use App\Models\Brief;
use App\Models\City;
use App\Models\Place;
use App\Models\Story;
use App\Support\JsonLdBuilder;

beforeEach(function () {
    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);
});

it('builds Restaurant schema for a restaurant place', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'chez-x',
        'name' => 'Chez X',
        'type' => 'restaurant',
        'description' => 'Bonne table.',
        'address' => 'rue du Pont 1',
        'neighborhood' => 'Centre',
        'latitude' => 50.4640,
        'longitude' => 4.8650,
        'contact' => ['phone' => '+32 81 00 00 00', 'website' => 'https://chez-x.be'],
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $schema = JsonLdBuilder::forPlace($place);

    expect($schema)
        ->toHaveKey('@context', 'https://schema.org')
        ->toHaveKey('@type', 'Restaurant')
        ->toHaveKey('name', 'Chez X')
        ->toHaveKey('description', 'Bonne table.')
        ->toHaveKey('telephone', '+32 81 00 00 00');

    expect($schema['geo'])
        ->toMatchArray(['@type' => 'GeoCoordinates', 'latitude' => 50.464, 'longitude' => 4.865]);

    expect($schema['address'])
        ->toMatchArray([
            '@type' => 'PostalAddress',
            'streetAddress' => 'rue du Pont 1',
            'addressLocality' => 'Centre',
            'addressCountry' => 'BE',
        ]);

    expect($schema['sameAs'])->toBe(['https://chez-x.be']);
});

it('builds TouristAttraction schema for patrimoine', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'citadelle',
        'name' => 'Citadelle',
        'type' => 'patrimoine',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    expect(JsonLdBuilder::forPlace($place)['@type'])->toBe('TouristAttraction');
});

it('omits geo block when coords missing', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'no-coords',
        'name' => 'No coords',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $schema = JsonLdBuilder::forPlace($place);

    expect($schema)->not->toHaveKey('geo');
});

it('builds Article schema for a story', function () {
    $story = Story::create([
        'city_id' => $this->city->id,
        'slug' => 'le-bia-bouquet',
        'title' => 'Le Bia Bouquet',
        'type' => Story::TYPE_TRADITION,
        'excerpt' => 'Tradition wallonne.',
        'content' => 'Texte long.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $schema = JsonLdBuilder::forStory($story);

    expect($schema)
        ->toHaveKey('@type', 'Article')
        ->toHaveKey('headline', 'Le Bia Bouquet')
        ->toHaveKey('description', 'Tradition wallonne.')
        ->toHaveKey('inLanguage', 'fr-BE')
        ->toHaveKey('dateModified');

    expect($schema['publisher'])
        ->toMatchArray(['@type' => 'Organization', 'name' => 'Bia Namur']);
});

it('builds Article schema for a brief with datePublished', function () {
    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Brief 19',
        'intro_text' => 'Première semaine.',
        'status' => Brief::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $schema = JsonLdBuilder::forBrief($brief);

    expect($schema)
        ->toHaveKey('@type', 'Article')
        ->toHaveKey('headline', 'Brief 19')
        ->toHaveKey('datePublished');
});

it('exposes jsonld in PlaceResource only on places.show route', function () {
    Place::create([
        'city_id' => $this->city->id,
        'slug' => 'visible',
        'name' => 'Visible',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    // Sur /lieu/{slug} : jsonld present
    $this->get('/lieu/visible')
        ->assertInertia(fn ($page) => $page
            ->has('place.jsonld')
            ->where('place.jsonld.@type', 'CafeOrCoffeeShop'),
        );

    // Sur /lieux : jsonld absent (collection)
    $this->get('/lieux')
        ->assertInertia(fn ($page) => $page
            ->has('places.0')
            ->missing('places.0.jsonld'),
        );
});
