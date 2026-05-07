<?php

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('magic-link-email:' . sha1('new-user@bia-namur.test'));
    RateLimiter::clear('magic-link-ip:' . sha1('127.0.0.1'));
});

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Auth/Login'));
});

it('creates a user, magic link and sends mail on first request', function () {
    $this->post('/auth/magic-link', ['email' => 'new-user@bia-namur.test'])
        ->assertRedirect();

    expect(User::where('email', 'new-user@bia-namur.test')->exists())->toBeTrue()
        ->and(MagicLink::where('email', 'new-user@bia-namur.test')->exists())->toBeTrue();

    Mail::assertSent(MagicLinkMail::class);
});

it('hashes the token and never stores it in plaintext', function () {
    $this->post('/auth/magic-link', ['email' => 'hashed@bia-namur.test']);

    $ml = MagicLink::where('email', 'hashed@bia-namur.test')->first();
    expect($ml->token_hash)->toBeString()->and(strlen($ml->token_hash))->toBe(64);

    // 64 chars = sha256 hex. Aucune trace du token brut nulle part.
    $rows = DB::table('magic_links')->where('email', 'hashed@bia-namur.test')->get();
    foreach ($rows as $r) {
        expect((array) $r)->not->toHaveKey('token');   // colonne inexistante
    }
});

it('logs the user in when the magic link token is valid', function () {
    $user = User::create([
        'name' => 'Test',
        'email' => 'login@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
        'email_verified_at' => now(),
    ]);

    [$rawToken, $tokenHash] = MagicLink::generateToken();
    MagicLink::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => $tokenHash,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->get("/auth/magic-link/{$rawToken}")
        ->assertRedirect('/');

    expect(auth()->user()?->id)->toBe($user->id);
});

it('rejects an expired magic link token', function () {
    $user = User::create([
        'name' => 'Expired',
        'email' => 'expired@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    [$rawToken, $tokenHash] = MagicLink::generateToken();
    MagicLink::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => $tokenHash,
        'expires_at' => now()->subMinute(),    // expired
    ]);

    $this->get("/auth/magic-link/{$rawToken}")
        ->assertRedirect('/login');

    expect(auth()->check())->toBeFalse();
});

it('rejects a magic link token already used', function () {
    $user = User::create([
        'name' => 'Used',
        'email' => 'used@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    [$rawToken, $tokenHash] = MagicLink::generateToken();
    MagicLink::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => $tokenHash,
        'expires_at' => now()->addMinutes(15),
        'used_at' => now()->subSeconds(5),    // already used
    ]);

    $this->get("/auth/magic-link/{$rawToken}")
        ->assertRedirect('/login');

    expect(auth()->check())->toBeFalse();
});

it('invalidates other active magic links for the same email after consumption', function () {
    $user = User::create([
        'name' => 'Multi',
        'email' => 'multi@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    [$tok1, $hash1] = MagicLink::generateToken();
    [$tok2, $hash2] = MagicLink::generateToken();

    MagicLink::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => $hash1,
        'expires_at' => now()->addMinutes(15),
    ]);
    MagicLink::create([
        'user_id' => $user->id,
        'email' => $user->email,
        'token_hash' => $hash2,
        'expires_at' => now()->addMinutes(15),
    ]);

    $this->get("/auth/magic-link/{$tok1}")->assertRedirect('/');

    $remaining = MagicLink::where('email', 'multi@bia-namur.test')
        ->whereNull('used_at')
        ->count();
    expect($remaining)->toBe(0);
});

it('logout invalidates the session', function () {
    $user = User::create([
        'name' => 'Logout',
        'email' => 'logout@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    $this->actingAs($user)->assertAuthenticated();
    $this->post('/logout')->assertRedirect('/');
    expect(auth()->check())->toBeFalse();
});
