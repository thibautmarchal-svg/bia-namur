<?php

use App\Services\Ingestion\EventCategorizationService;

beforeEach(function () {
    // Charge les regles reelles depuis config/bia.php
});

it('categorizes a concert based on the title', function () {
    $service = new EventCategorizationService;
    expect($service->categorize('Concert : Sweet Lord Trio'))->toBe('concert');
});

it('categorizes an exhibition based on description', function () {
    $service = new EventCategorizationService;
    expect($service->categorize('Quelque chose', 'Vernissage de l\'expo carnets de voyage'))->toBe('expo');
});

it('falls back to source category if no rule matches title/description', function () {
    $service = new EventCategorizationService;
    expect($service->categorize('Brocante annuelle', 'Brocante de quartier'))->toBe('marche');
});

it('returns null when nothing matches', function () {
    $service = new EventCategorizationService;
    expect($service->categorize('Truc', 'machin', null))->toBeNull();
});

it('is case-insensitive', function () {
    $service = new EventCategorizationService;
    expect($service->categorize('CONCERT JAZZ', null))->toBe('concert');
});

it('matches accents (or absence) without normalization', function () {
    $service = new EventCategorizationService;
    // Le mot "marché" avec accent dans la regle config = "marché"
    expect($service->categorize('Le marché du dimanche'))->toBe('marche');
});
