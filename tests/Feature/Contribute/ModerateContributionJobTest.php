<?php

use App\Jobs\ModerateContributionJob;
use App\Models\AiRun;
use App\Models\Contribution;
use App\Services\Ai\ClaudeApiService;

beforeEach(function () {
    config()->set('bia.ai.mock_mode', true);
});

it('moderates a contribution and sets status auto_approved on score >= 75', function () {
    // Fixture moderation_mock_v1.json a score=78 → auto_approved
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => [
            'name' => 'Test café',
            'type' => 'cafe',
            'description' => 'Un café charmant en plein centre.',
            'address' => 'Place Saint-Aubain 1',
        ],
        'status' => Contribution::STATUS_PENDING,
    ]);

    $result = (new ModerateContributionJob($contribution->id))->handle(new ClaudeApiService(mockMode: true));

    expect($result->status)->toBe(Contribution::STATUS_AUTO_APPROVED)
        ->and($result->ai_score)->toBe(78)
        ->and($result->ai_prompt_version)->toBe('moderation_v1')
        ->and($result->ai_reasoning)->toBeArray()
        ->and($result->ai_reasoning)->toHaveKey('quality');
});

it('does not send PII (email/name) to Claude in the prompt', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => [
            'name' => 'Test',
            'type' => 'cafe',
            'description' => 'Description test ' . str_repeat('x', 30),
            'contributor_email' => 'leak@example.com',
            'contributor_name' => 'Leak Name',
        ],
        'status' => Contribution::STATUS_PENDING,
    ]);

    // Le job devrait passer mais sans envoyer email/contributor_name a Claude
    $job = new ModerateContributionJob($contribution->id);

    // On verifie que le buildUserMessage ne contient ni l'email ni le nom contributeur
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('buildUserMessage');
    $method->setAccessible(true);
    $message = $method->invoke($job, $contribution);

    expect($message)->not->toContain('leak@example.com')
        ->and($message)->not->toContain('Leak Name')
        ->and($message)->toContain('Test');
});

it('logs an ai_run on each moderation', function () {
    $contribution = Contribution::create([
        'type' => Contribution::TYPE_PLACE_SUGGESTION,
        'payload' => ['name' => 'X', 'type' => 'cafe', 'description' => str_repeat('y', 50)],
        'status' => Contribution::STATUS_PENDING,
    ]);

    expect(AiRun::count())->toBe(0);

    (new ModerateContributionJob($contribution->id))->handle(new ClaudeApiService(mockMode: true));

    expect(AiRun::count())->toBe(1)
        ->and(AiRun::first()->type)->toBe(AiRun::TYPE_CONTRIBUTION_MODERATION);
});
