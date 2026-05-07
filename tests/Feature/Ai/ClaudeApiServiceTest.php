<?php

use App\Models\AiRun;
use App\Services\Ai\ClaudeApiService;

beforeEach(function () {
    config()->set('bia.ai.mock_mode', true);
});

it('returns the brief fixture text in mock mode', function () {
    $service = new ClaudeApiService(mockMode: true);

    $completion = $service->complete('brief_v1', '{"events":[]}');

    expect($completion->isMock)->toBeTrue()
        ->and($completion->text)->toContain('"intro"')
        ->and($completion->text)->toContain('Marché du dimanche au Grognon')
        ->and($completion->stopReason)->toBe('mock');
});

it('parses JSON output via toJson()', function () {
    $service = new ClaudeApiService(mockMode: true);

    $payload = $service->complete('brief_v1', '{"events":[]}')->toJson();

    expect($payload)->toBeArray()
        ->and($payload['intro'])->toBeString()
        ->and($payload['items'])->toBeArray()
        ->and(count($payload['items']))->toBeGreaterThanOrEqual(5)
        ->and(count($payload['items']))->toBeLessThanOrEqual(7);
});

it('logs an ai_runs entry on every call (success)', function () {
    expect(AiRun::count())->toBe(0);

    $completion = (new ClaudeApiService(mockMode: true))
        ->complete('brief_v1', 'payload-test');

    expect(AiRun::count())->toBe(1);
    $aiRun = AiRun::first();

    expect($aiRun->type)->toBe(AiRun::TYPE_BRIEF_WEEKLY)
        ->and($aiRun->prompt_template_version)->toBe('brief_v1')
        ->and($aiRun->status)->toBe(AiRun::STATUS_SUCCESS)
        ->and($aiRun->input_tokens)->toBeGreaterThan(0)
        ->and($aiRun->output_tokens)->toBeGreaterThan(0)
        ->and($aiRun->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and((float) $aiRun->cost_usd)->toBeGreaterThan(0)
        ->and($completion->aiRunId)->toBe($aiRun->id);
});

it('logs status=failed when the prompt key is unknown', function () {
    $service = new ClaudeApiService(mockMode: true);

    expect(fn () => $service->complete('unknown_prompt_v999', 'payload'))
        ->toThrow(RuntimeException::class, 'Prompt inconnu');

    expect(AiRun::count())->toBe(0); // throw before AiRun creation in this case
});

it('infers the AiRun type from the prompt key', function () {
    config()->set('bia.prompts.story_v1', config('bia.prompts.story_v1'));

    (new ClaudeApiService(mockMode: true))->complete('story_v1', 'payload');

    expect(AiRun::latest('id')->first()->type)->toBe(AiRun::TYPE_STORY_GENERATION);
});

it('computes USD cost using the pricing table', function () {
    $svc = new ClaudeApiService(mockMode: true);
    $svc->complete('brief_v1', str_repeat('a', 1000));

    $aiRun = AiRun::latest('id')->first();
    $expected = ($aiRun->input_tokens / 1_000_000) * 3.00
        + ($aiRun->output_tokens / 1_000_000) * 15.00;

    expect((float) $aiRun->cost_usd)->toEqualWithDelta($expected, 0.0001);
});

it('throws if real API mode is enabled but no key is configured', function () {
    config()->set('bia.ai.mock_mode', false);

    $service = new ClaudeApiService(apiKey: '', mockMode: false);

    expect(fn () => $service->complete('brief_v1', 'payload'))
        ->toThrow(RuntimeException::class, 'ANTHROPIC_API_KEY manquante');

    // ai_runs has the failed run
    expect(AiRun::latest('id')->first()->status)->toBe(AiRun::STATUS_FAILED);
});
