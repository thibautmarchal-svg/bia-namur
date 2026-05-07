<?php

use App\Models\City;
use App\Models\Favorite;
use App\Models\Place;
use App\Models\Story;
use App\Models\User;

beforeEach(function () {
    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);

    $this->user = User::create([
        'name' => 'Tibo',
        'email' => 'tibo@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
        'email_verified_at' => now(),
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
        'content' => 'Histoire wallonne.',
        'status' => Story::STATUS_PUBLISHED,
    ]);
});

it('redirects to login when guest tries to toggle', function () {
    $this->post('/favoris/toggle', ['type' => 'place', 'id' => $this->place->id])
        ->assertRedirect('/login');
});

it('blocks /mes-favoris for guests', function () {
    $this->get('/mes-favoris')->assertRedirect('/login');
});

it('adds a place to favorites', function () {
    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'place', 'id' => $this->place->id])
        ->assertRedirect();

    expect(Favorite::count())->toBe(1)
        ->and($this->user->favorites()->count())->toBe(1);
});

it('toggles off a place when already favorited', function () {
    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Place::class,
        'favoritable_id' => $this->place->id,
    ]);

    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'place', 'id' => $this->place->id])
        ->assertRedirect();

    expect(Favorite::count())->toBe(0);
});

it('adds a story to favorites with correct polymorphic type', function () {
    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'story', 'id' => $this->story->id])
        ->assertRedirect();

    expect(Favorite::where('favoritable_type', Story::class)->count())->toBe(1);
});

it('rejects unknown type', function () {
    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'event', 'id' => 1])
        ->assertSessionHasErrors('type');
});

it('returns 404 for draft places', function () {
    $draft = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'draft',
        'name' => 'Draft',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_DRAFT,
    ]);

    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'place', 'id' => $draft->id])
        ->assertNotFound();
});

it('blocks adding beyond free limit', function () {
    config(['bia.favorites.free_limit' => 2]);

    // 2 favoris
    foreach (range(1, 2) as $i) {
        $p = Place::create([
            'city_id' => $this->city->id,
            'slug' => "fav-$i",
            'name' => "Fav $i",
            'type' => 'cafe',
            'source' => Place::SOURCE_ADMIN,
            'status' => Place::STATUS_PUBLISHED,
        ]);
        Favorite::create([
            'user_id' => $this->user->id,
            'favoritable_type' => Place::class,
            'favoritable_id' => $p->id,
        ]);
    }

    // tentative 3e
    $this->actingAs($this->user)
        ->post('/favoris/toggle', ['type' => 'place', 'id' => $this->place->id])
        ->assertRedirect()
        ->assertSessionHas('flash.type', 'limit');

    expect($this->user->favorites()->count())->toBe(2);
});

it('renders mes-favoris with empty state', function () {
    $this->actingAs($this->user)
        ->get('/mes-favoris')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Favorites/Index')
            ->where('count', 0)
            ->where('limit', 20)
            ->has('places', 0)
            ->has('stories', 0),
        );
});

it('renders mes-favoris with mixed places and stories', function () {
    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Place::class,
        'favoritable_id' => $this->place->id,
    ]);
    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Story::class,
        'favoritable_id' => $this->story->id,
    ]);

    $this->actingAs($this->user)
        ->get('/mes-favoris')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('count', 2)
            ->has('places', 1)
            ->has('stories', 1)
            ->where('places.0.slug', 'le-delta')
            ->where('stories.0.slug', 'le-bia-bouquet'),
        );
});

it('shares favorite ids in inertia auth props', function () {
    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Place::class,
        'favoritable_id' => $this->place->id,
    ]);

    $this->actingAs($this->user)
        ->get('/lieux')
        ->assertInertia(fn ($page) => $page
            ->where('auth.favorites.places.0', $this->place->id)
            ->has('auth.favorites.stories', 0),
        );
});

it('does not expose favorites for guests', function () {
    $this->get('/lieux')
        ->assertInertia(fn ($page) => $page
            ->has('auth.favorites.places', 0)
            ->has('auth.favorites.stories', 0),
        );
});
