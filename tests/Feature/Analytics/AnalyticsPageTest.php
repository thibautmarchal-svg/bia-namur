<?php

use App\Filament\Widgets\PageViewsTrendWidget;
use App\Filament\Widgets\TopReferrersWidget;
use App\Filament\Widgets\ViewsByTypeWidget;
use App\Models\Brief;
use App\Models\City;
use App\Models\PageView;
use App\Models\Place;
use App\Models\User;
use Filament\Tables\Table;

beforeEach(function () {
    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);

    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_ADMIN,
        'subscription_tier' => User::TIER_FREE,
    ]);

    $this->member = User::create([
        'name' => 'Member',
        'email' => 'member@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);
});

it('blocks unauthenticated access to /admin/analytics', function () {
    $this->get('/admin/analytics')->assertRedirect('/login');
});

it('blocks members (non-moderator) from analytics', function () {
    $this->actingAs($this->member)
        ->get('/admin/analytics')
        ->assertForbidden();
});

it('admin can render the analytics page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/analytics')
        ->assertOk()
        ->assertSeeText('Analytics');
});

it('PageViewsTrendWidget computes daily counts excluding bots', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'p1',
        'name' => 'P1',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    PageView::create([
        'viewable_type' => Place::class,
        'viewable_id' => $place->id,
        'slug' => 'p1',
        'ip_hash' => str_repeat('a', 64),
        'is_bot' => false,
        'viewed_at' => now()->subDays(2),
    ]);
    PageView::create([
        'viewable_type' => Place::class,
        'viewable_id' => $place->id,
        'slug' => 'p1',
        'ip_hash' => str_repeat('b', 64),
        'is_bot' => true, // bot, doit etre exclu
        'viewed_at' => now()->subDays(2),
    ]);

    $widget = app(PageViewsTrendWidget::class);
    $widget->filter = '7';
    $data = invade($widget)->getData();

    $total = array_sum($data['datasets'][0]['data']);
    expect($total)->toBe(1)
        ->and($data['labels'])->toHaveCount(7);
});

it('ViewsByTypeWidget splits views by viewable_type', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'p1',
        'name' => 'P1',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);
    $brief = Brief::create([
        'city_id' => $this->city->id,
        'year' => 2026,
        'week_number' => 19,
        'slug' => '2026-W19',
        'title' => 'Brief',
        'status' => Brief::STATUS_PUBLISHED,
    ]);

    foreach (range(1, 3) as $i) {
        PageView::create([
            'viewable_type' => Place::class,
            'viewable_id' => $place->id,
            'slug' => 'p1',
            'ip_hash' => str_repeat((string) $i, 64),
            'is_bot' => false,
            'viewed_at' => now(),
        ]);
    }
    PageView::create([
        'viewable_type' => Brief::class,
        'viewable_id' => $brief->id,
        'slug' => '2026-W19',
        'ip_hash' => str_repeat('z', 64),
        'is_bot' => false,
        'viewed_at' => now(),
    ]);

    $widget = app(ViewsByTypeWidget::class);
    $widget->filter = '7';
    $data = invade($widget)->getData();

    expect($data['datasets'][0]['data'])->toBe([3, 0, 1])
        ->and($data['labels'])->toBe(['Lieux', 'Stories', 'Briefs']);
});

it('TopReferrersWidget excludes null referrers and bots', function () {
    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'p1',
        'name' => 'P1',
        'type' => 'cafe',
        'source' => Place::SOURCE_ADMIN,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    PageView::create([
        'viewable_type' => Place::class,
        'viewable_id' => $place->id,
        'slug' => 'p1',
        'ip_hash' => str_repeat('a', 64),
        'referrer_host' => 'twitter.com',
        'is_bot' => false,
        'viewed_at' => now(),
    ]);
    PageView::create([
        'viewable_type' => Place::class,
        'viewable_id' => $place->id,
        'slug' => 'p1',
        'ip_hash' => str_repeat('b', 64),
        'referrer_host' => null,    // doit etre exclu
        'is_bot' => false,
        'viewed_at' => now(),
    ]);
    PageView::create([
        'viewable_type' => Place::class,
        'viewable_id' => $place->id,
        'slug' => 'p1',
        'ip_hash' => str_repeat('c', 64),
        'referrer_host' => 'reddit.com',
        'is_bot' => true,    // bot, exclu
        'viewed_at' => now(),
    ]);

    $widget = app(TopReferrersWidget::class);
    $rows = $widget->table(Table::make($widget))
        ->getQuery()
        ->get()
        ->all();

    expect($rows)->toHaveCount(1);
    expect($rows[0]->referrer_host)->toBe('twitter.com');
});
