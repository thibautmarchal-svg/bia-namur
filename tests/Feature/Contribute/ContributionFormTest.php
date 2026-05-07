<?php

use App\Jobs\ModerateContributionJob;
use App\Models\Contribution;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('renders the contribute form', function () {
    $this->get('/contribuer')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('Contribute/Form')
            ->has('types'),
        );
});

it('stores a valid contribution and dispatches the moderation job', function () {
    $payload = [
        'name' => 'Le Café du Marché',
        'type' => 'cafe',
        'description' => 'Un café familial sur la place du marché, spécialité de gaufres maison et café équitable.',
        'address' => 'Place Saint-Aubain 5, 5000 Namur',
        'neighborhood' => 'Centre',
        'why' => 'Atmosphère chaleureuse, terrasse au soleil le samedi matin.',
        'contributor_email' => 'thibaut@example.be',
        'contributor_name' => 'Thibaut',
    ];

    $this->post('/contribuer', $payload)
        ->assertRedirect('/contribuer/merci');

    expect(Contribution::count())->toBe(1);

    $contribution = Contribution::first();
    expect($contribution->type)->toBe(Contribution::TYPE_PLACE_SUGGESTION)
        ->and($contribution->status)->toBe(Contribution::STATUS_PENDING)
        ->and($contribution->payload['name'])->toBe('Le Café du Marché')
        ->and($contribution->payload['type'])->toBe('cafe')
        ->and($contribution->payload['contributor_email'])->toBe('thibaut@example.be');

    Queue::assertPushed(ModerateContributionJob::class);
});

it('rejects a submission without a name', function () {
    $this->post('/contribuer', [
        'type' => 'cafe',
        'description' => 'Une description suffisamment longue pour passer la validation min:30.',
    ])->assertSessionHasErrors('name');

    expect(Contribution::count())->toBe(0);
});

it('rejects a submission with too short a description', function () {
    $this->post('/contribuer', [
        'name' => 'Test',
        'type' => 'cafe',
        'description' => 'Trop court',
    ])->assertSessionHasErrors('description');
});

it('rejects a submission with an invalid type', function () {
    $this->post('/contribuer', [
        'name' => 'Test Place',
        'type' => 'unknown_type_zzz',
        'description' => 'Une description suffisamment longue pour passer la validation min:30.',
    ])->assertSessionHasErrors('type');
});

it('blocks a submission when the honeypot is filled (bot detected)', function () {
    $this->post('/contribuer', [
        'website_url' => 'http://spam.example.com',  // honeypot triggered
        'name' => 'Spam Place',
        'type' => 'cafe',
        'description' => 'Une description suffisamment longue pour passer la validation min:30.',
    ])->assertSessionHasErrors('website_url');

    expect(Contribution::count())->toBe(0);
});

it('renders the thanks page', function () {
    $this->get('/contribuer/merci')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Contribute/Thanks'));
});
