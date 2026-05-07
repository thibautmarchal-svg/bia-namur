<?php

use App\Models\City;
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

it('renders /wallon with the configured words and families', function () {
    $this->get('/wallon')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Wallon')
            ->has('words')
            ->has('families')
            ->where('words.0.word', 'bia')
            ->where('words.0.definition', 'beau'),
        );
});

it('groups wallon words by family in the rendered props', function () {
    $response = $this->get('/wallon');
    $response->assertOk();

    $props = $response->viewData('page')['props'] ?? [];
    expect($props['words'])->toBeArray();

    $families = collect($props['words'])->pluck('family')->unique()->values()->all();
    expect($families)->toContain('expression', 'tradition', 'quotidien');
});

it('lists published wallon stories on the /wallon page', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'expressions-namuroises',
        'title' => 'Petit lexique pour la place du marché',
        'type' => Story::TYPE_WALLON,
        'excerpt' => 'Quelques formules à comprendre pour ne pas avoir l\'air biesse.',
        'content' => 'Du contenu test pour la story wallon.',
        'status' => Story::STATUS_PUBLISHED,
    ]);

    $this->get('/wallon')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->has('stories', 1)
            ->where('stories.0.slug', 'expressions-namuroises'),
        );
});

it('does not surface draft wallon stories', function () {
    Story::create([
        'city_id' => $this->city->id,
        'slug' => 'draft-wallon',
        'title' => 'Brouillon',
        'type' => Story::TYPE_WALLON,
        'content' => 'wip',
        'status' => Story::STATUS_DRAFT,
    ]);

    $this->get('/wallon')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('stories', 0));
});

it('renders /a-propos', function () {
    $this->get('/a-propos')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('About'));
});
