<?php

use App\Services\Ai\ClaudeCompletion;

function makeCompletion(string $text): ClaudeCompletion
{
    return new ClaudeCompletion(
        text: $text,
        model: 'claude-haiku-4-5',
        inputTokens: 10,
        outputTokens: 5,
        stopReason: 'end_turn',
    );
}

it('parses pure JSON without wrapping', function () {
    $json = '{"intro":"Salut Namur","items":[]}';
    expect(makeCompletion($json)->toJson())->toMatchArray([
        'intro' => 'Salut Namur',
        'items' => [],
    ]);
});

it('strips markdown json fences (```json ... ```)', function () {
    $wrapped = "```json\n{\"intro\":\"Test\",\"items\":[1,2]}\n```";
    expect(makeCompletion($wrapped)->toJson())->toBe([
        'intro' => 'Test',
        'items' => [1, 2],
    ]);
});

it('strips bare markdown fences (``` ... ```)', function () {
    $wrapped = "```\n{\"key\":\"val\"}\n```";
    expect(makeCompletion($wrapped)->toJson())->toBe(['key' => 'val']);
});

it('extracts JSON when text is prepended (greedy first brace to last brace)', function () {
    $messy = "Voici ton brief :\n{\"intro\":\"Coucou\",\"items\":[]}\n\nJ'espere que ca te convient !";
    expect(makeCompletion($messy)->toJson())->toBe([
        'intro' => 'Coucou',
        'items' => [],
    ]);
});

it('throws a clear RuntimeException with a preview when truly invalid', function () {
    $junk = 'Je ne peux pas répondre à cette question.';
    expect(fn () => makeCompletion($junk)->toJson())
        ->toThrow(\RuntimeException::class, 'pas du JSON valide');
});

it('throws when text decodes to a non-array (e.g. a JSON string)', function () {
    $text = '"just a string"';
    expect(fn () => makeCompletion($text)->toJson())
        ->toThrow(\RuntimeException::class);
});

it('handles JSON arrays (not just objects)', function () {
    $array = '[{"a":1},{"a":2}]';
    expect(makeCompletion($array)->toJson())->toHaveCount(2);
});
