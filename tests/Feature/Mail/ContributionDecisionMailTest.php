<?php

use App\Mail\ContributionDecisionMail;
use App\Models\City;
use App\Models\Contribution;
use App\Models\Place;
use App\Models\User;

beforeEach(function () {
    $this->city = City::create([
        'slug' => 'namur',
        'name' => 'Namur',
        'latitude' => 50.4674,
        'longitude' => 4.8718,
        'primary_color' => '#C77F2C',
    ]);
});

it('rejects an invalid decision in constructor', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(fn () => new ContributionDecisionMail($contribution, 'invalid_state'))
        ->toThrow(InvalidArgumentException::class);
});

it('builds the approved mail with subject and view data', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => [
            'name' => 'Le Bia Bouquet',
            'contributor_name' => 'Marie',
        ],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'le-bia-bouquet',
        'name' => 'Le Bia Bouquet',
        'type' => 'bar',
        'source' => Place::SOURCE_CONTRIBUTION,
        'status' => Place::STATUS_PUBLISHED,
    ]);

    $mail = new ContributionDecisionMail(
        $contribution,
        ContributionDecisionMail::DECISION_APPROVED,
        $place,
    );

    expect($mail->envelope()->subject)->toBe('Ta contribution est en ligne');

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('Salut Marie,')
        ->toContain('Le Bia Bouquet')
        ->toContain('/lieu/le-bia-bouquet')
        ->toContain('Voir la fiche');
});

it('hides the place button when place is still draft', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'Café X'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $place = Place::create([
        'city_id' => $this->city->id,
        'slug' => 'cafe-x',
        'name' => 'Café X',
        'type' => 'cafe',
        'source' => Place::SOURCE_CONTRIBUTION,
        'status' => Place::STATUS_DRAFT,
    ]);

    $mail = new ContributionDecisionMail(
        $contribution,
        ContributionDecisionMail::DECISION_APPROVED,
        $place,
    );

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('On finalise quelques détails')
        ->not->toContain('Voir la fiche');
});

it('includes the reviewer note for needs_changes decision', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'Lieu Y', 'contributor_name' => 'Pierre'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $mail = new ContributionDecisionMail(
        $contribution,
        ContributionDecisionMail::DECISION_NEEDS_CHANGES,
        null,
        'Pourrais-tu vérifier l\'adresse exacte ?',
    );

    expect($mail->envelope()->subject)->toBe('Une petite précision sur ta contribution');

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('Mot de l')
        ->toContain('Pourrais-tu vérifier l');
});

it('builds the rejected mail without reviewer note when none given', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'Pub déguisée'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $mail = new ContributionDecisionMail(
        $contribution,
        ContributionDecisionMail::DECISION_REJECTED,
    );

    expect($mail->envelope()->subject)->toBe('On revient vers toi à propos de ta contribution');

    $rendered = $mail->render();
    expect($rendered)
        ->toContain('On revient vers toi')
        ->toContain('en proposer une autre')
        ->not->toContain('Mot de l');
});

it('resolves recipient from user account when user_id is set', function () {
    $user = User::create([
        'name' => 'Tibo',
        'email' => 'tibo@bia-namur.test',
        'password' => bcrypt('x'),
        'role' => User::ROLE_MEMBER,
        'subscription_tier' => User::TIER_FREE,
    ]);

    $contribution = Contribution::create([
        'user_id' => $user->id,
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(ContributionDecisionMail::recipientFor($contribution))
        ->toBe('tibo@bia-namur.test');
});

it('resolves recipient from payload contributor_email for anonymous contribution', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => [
            'name' => 'X',
            'contributor_email' => 'anon@bia-namur.test',
        ],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(ContributionDecisionMail::recipientFor($contribution))
        ->toBe('anon@bia-namur.test');
});

it('returns null recipient when contribution is fully anonymous', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(ContributionDecisionMail::recipientFor($contribution))->toBeNull();
});

it('falls back to "toi" when no contributor_name is in payload', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X'],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $mail = new ContributionDecisionMail(
        $contribution,
        ContributionDecisionMail::DECISION_APPROVED,
    );

    expect($mail->render())->toContain('Salut toi,');
});
