<?php

use App\Jobs\SendBriefPublishedNotificationJob;
use App\Models\Brief;
use App\Models\City;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

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
    ]);
});

it('rejects unauthenticated subscribe', function () {
    $this->postJson('/push/subscribe', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        'keys' => ['p256dh' => 'k1', 'auth' => 'k2'],
    ])->assertUnauthorized();
});

it('stores a new subscription', function () {
    $this->actingAs($this->user)
        ->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc-123',
            'keys' => ['p256dh' => 'pubkey-base64', 'auth' => 'authtoken-base64'],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(PushSubscription::count())->toBe(1);
    $sub = PushSubscription::first();
    expect($sub->user_id)->toBe($this->user->id)
        ->and($sub->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/abc-123')
        ->and($sub->endpoint_hash)->toHaveLength(64);
});

it('updates an existing subscription instead of duplicating', function () {
    PushSubscription::create([
        'user_id' => $this->user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        'endpoint_hash' => PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/abc'),
        'p256dh_key' => 'old-pub',
        'auth_token' => 'old-auth',
    ]);

    $this->actingAs($this->user)
        ->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'new-pub', 'auth' => 'new-auth'],
        ])
        ->assertOk();

    expect(PushSubscription::count())->toBe(1);
    expect(PushSubscription::first()->p256dh_key)->toBe('new-pub');
});

it('validates endpoint as URL', function () {
    $this->actingAs($this->user)
        ->postJson('/push/subscribe', [
            'endpoint' => 'not-a-url',
            'keys' => ['p256dh' => 'k1', 'auth' => 'k2'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('requires both p256dh and auth keys', function () {
    $this->actingAs($this->user)
        ->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
            'keys' => ['p256dh' => 'k1'],
        ])
        ->assertUnprocessable();
});

it('unsubscribes only own subscription', function () {
    $other = User::create([
        'name' => 'Other',
        'email' => 'other@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    $endpoint = 'https://fcm.googleapis.com/fcm/send/shared';
    PushSubscription::create([
        'user_id' => $other->id,
        'endpoint' => $endpoint,
        'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
        'p256dh_key' => 'k', 'auth_token' => 'a',
    ]);

    // L'utilisateur courant n'a aucune sub avec cet endpoint
    $this->actingAs($this->user)
        ->postJson('/push/unsubscribe', ['endpoint' => $endpoint])
        ->assertOk()
        ->assertJson(['ok' => true, 'deleted' => 0]);

    // La sub de l'autre user n'a pas ete touchee
    expect(PushSubscription::count())->toBe(1);
});

it('exposes vapid public key via inertia shared props', function () {
    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('pushVapidPublicKey', config('bia.push.vapid_public_key')),
        );
});

it('observer dispatches job when brief transitions to published', function () {
    Queue::fake();

    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Cette semaine',
        'status' => Brief::STATUS_PENDING_REVIEW,
    ]);

    Queue::assertNothingPushed();

    $brief->update(['status' => Brief::STATUS_PUBLISHED, 'published_at' => now()]);

    Queue::assertPushed(
        SendBriefPublishedNotificationJob::class,
        fn ($job) => $job->briefId === $brief->id,
    );
});

it('observer does not re-dispatch on subsequent saves of already-published brief', function () {
    Queue::fake();

    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'X',
        'status' => Brief::STATUS_PUBLISHED,
    ]);

    Queue::assertNothingPushed();

    $brief->update(['title' => 'Updated title']);
    Queue::assertNothingPushed();
});

it('job exits early when push disabled', function () {
    config(['bia.push.enabled' => false]);

    PushSubscription::create([
        'user_id' => $this->user->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/x',
        'endpoint_hash' => PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/x'),
        'p256dh_key' => 'k', 'auth_token' => 'a',
    ]);

    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'X',
        'status' => Brief::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $job = new SendBriefPublishedNotificationJob($brief->id);
    $job->handle(app(\App\Services\Push\WebPushService::class));

    // Pas d'erreur, juste un log : aucune sub ne change
    expect(PushSubscription::first()->last_used_at)->toBeNull();
});

it('job is no-op if brief is not published', function () {
    config(['bia.push.enabled' => true]);

    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'X',
        'status' => Brief::STATUS_DRAFT_AI,
    ]);

    $job = new SendBriefPublishedNotificationJob($brief->id);
    $job->handle(app(\App\Services\Push\WebPushService::class));

    expect(true)->toBeTrue(); // pas d'exception
});
