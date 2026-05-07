<?php

use App\Models\City;
use App\Models\Contribution;
use App\Models\Favorite;
use App\Models\MagicLink;
use App\Models\Place;
use App\Models\PushSubscription;
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
        'locale' => 'fr',
    ]);
});

it('redirects guests to login on /mon-compte', function () {
    $this->get('/mon-compte')->assertRedirect('/login');
});

it('renders account page for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/mon-compte')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Account/Show')
            ->where('user.email', 'tibo@bia-namur.test')
            ->has('stats.contributions')
            ->has('stats.favorites'),
        );
});

it('updates name and locale via PUT', function () {
    $this->actingAs($this->user)
        ->put('/mon-compte', ['name' => 'Thibaut', 'locale' => 'en'])
        ->assertRedirect();

    expect($this->user->fresh()->name)->toBe('Thibaut')
        ->and($this->user->fresh()->locale)->toBe('en');
});

it('rejects invalid locale', function () {
    $this->actingAs($this->user)
        ->put('/mon-compte', ['name' => 'Thibaut', 'locale' => 'xx'])
        ->assertSessionHasErrors('locale');
});

it('returns 401 for export when not authenticated', function () {
    $this->get('/me/export')->assertRedirect('/login');
});

it('exports complete user data as JSON', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-delta',
        'name' => 'Le Delta',
        'type' => 'culture',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    Contribution::create([
        'user_id' => $this->user->id,
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X', 'description' => 'desc'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Place::class,
        'favoritable_id' => $place->id,
    ]);

    PushSubscription::create([
        'user_id' => $this->user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        'endpoint_hash' => PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/abc'),
        'p256dh_key' => 'k', 'auth_token' => 'a',
    ]);

    $response = $this->actingAs($this->user)->get('/me/export');

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment; filename=')
        ->toContain('bia-namur-export-');

    $body = json_decode($response->getContent(), true);
    expect($body)
        ->toHaveKey('export_format_version', '1.0')
        ->toHaveKey('exported_at')
        ->toHaveKey('profile.email', 'tibo@bia-namur.test')
        ->toHaveKey('contributions');

    expect($body['contributions'])->toHaveCount(1);
    expect($body['favorites'])->toHaveCount(1);
    expect($body['favorites'][0]['type'])->toBe('Place');
    expect($body['favorites'][0]['slug'])->toBe('le-delta');
    expect($body['push_subscriptions'])->toHaveCount(1);
});

it('export does not leak password or stripe_customer_id', function () {
    $body = json_decode(
        $this->actingAs($this->user)->get('/me/export')->getContent(),
        true,
    );

    expect($body['profile'])
        ->not->toHaveKey('password')
        ->not->toHaveKey('stripe_customer_id')
        ->not->toHaveKey('remember_token');
});

it('rejects delete without confirm_email', function () {
    $this->actingAs($this->user)
        ->post('/me/delete')
        ->assertSessionHasErrors('confirm_email');
});

it('rejects delete with wrong confirm_email', function () {
    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => 'wrong@email.com'])
        ->assertSessionHasErrors('confirm_email');

    expect(User::find($this->user->id))->not->toBeNull();    // pas supprime
});

it('deletes account when confirm_email matches', function () {
    Favorite::create([
        'user_id' => $this->user->id,
        'favoritable_type' => Place::class,
        'favoritable_id' => 1,
    ]);

    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => $this->user->email])
        ->assertRedirect('/');

    expect(User::find($this->user->id))->toBeNull();    // soft-deleted
    expect(User::withTrashed()->find($this->user->id))->not->toBeNull();    // mais retrouvable

    expect(Favorite::where('user_id', $this->user->id)->count())->toBe(0);
});

it('anonymizes contributions instead of deleting them', function () {
    $contribution = Contribution::create([
        'user_id' => $this->user->id,
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => [
            'name' => 'Important contribution',
            'description' => 'Lieu important',
            'contributor_email' => $this->user->email,
            'contributor_name' => $this->user->name,
        ],
        'status' => Contribution::STATUS_AUTO_APPROVED,
    ]);

    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => $this->user->email])
        ->assertRedirect();

    $kept = Contribution::find($contribution->id);
    expect($kept)->not->toBeNull()
        ->and($kept->user_id)->toBeNull();

    // Le contenu editorial reste
    expect($kept->payload['name'])->toBe('Important contribution')
        ->and($kept->payload['description'])->toBe('Lieu important');

    // Les PII personnelles sont purgees (cle present mais a null, ou absente)
    expect($kept->payload['contributor_email'] ?? null)->toBeNull();
    expect($kept->payload['contributor_name'] ?? null)->toBeNull();
});

it('logs out the user after deletion', function () {
    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => $this->user->email])
        ->assertRedirect('/');

    // Apres delete : user n'est plus connecte
    $this->get('/mon-compte')->assertRedirect('/login');
});

it('case-insensitive email confirm', function () {
    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => 'TIBO@bia-namur.test'])
        ->assertRedirect('/');

    expect(User::find($this->user->id))->toBeNull();
});

it('deletes magic links and push subs alongside', function () {
    MagicLink::create([
        'user_id' => $this->user->id,
        'email' => $this->user->email,
        'token_hash' => str_repeat('a', 64),
        'expires_at' => now()->addMinutes(15),
    ]);
    PushSubscription::create([
        'user_id' => $this->user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/x',
        'endpoint_hash' => PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/x'),
        'p256dh_key' => 'k', 'auth_token' => 'a',
    ]);

    $this->actingAs($this->user)
        ->post('/me/delete', ['confirm_email' => $this->user->email]);

    expect(MagicLink::where('user_id', $this->user->id)->count())->toBe(0);
    expect(PushSubscription::where('user_id', $this->user->id)->count())->toBe(0);
});
