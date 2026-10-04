<?php

/** Coverage for grouped emotion scoring. */

use Strukt\Owl\Emotion\EmotionAnalyzer;

test('a joyful sentence is dominated by joy', function (): void {
    $result = (new EmotionAnalyzer())->analyze('This product is absolutely fantastic! I love it.');

    expect($result['dominant'])->toBe('joy')
        ->and($result['matches'])->toBe(1)
        ->and($result['emotions']['joy'])->toBe(1.0);
});

test('a hostile sentence is dominated by anger', function (): void {
    $result = (new EmotionAnalyzer())->analyze('This is terrible and broken, I hate it!!!');

    expect($result['dominant'])->toBe('anger')
        ->and($result['emotions']['anger'])->toBe(1.0);
});

test('emotion shares are ordered from dominant to weakest', function (): void {
    $emotions = (new EmotionAnalyzer())->analyze('happy and sad and angry at the same time')['emotions'];
    $values = array_values($emotions);
    $sorted = $values;
    rsort($sorted);

    expect($values)->toBe($sorted)
        ->and(array_key_first($emotions))->toBe('joy');
});

test('emotion shares add up to one when anything matched', function (): void {
    $emotions = (new EmotionAnalyzer())->analyze('happy and sad and angry at the same time')['emotions'];

    expect(array_sum($emotions))->toEqualWithDelta(1.0, 0.0001);
});

test('text with no emotion words reports no matches', function (): void {
    $result = (new EmotionAnalyzer())->analyze('The report was filed on Tuesday by the committee.');

    expect($result['matches'])->toBe(0)
        ->and(array_sum($result['emotions']))->toBe(0.0);
});

test('empty text is handled without an error', function (): void {
    expect((new EmotionAnalyzer())->analyze(''))
        ->toMatchArray(['dominant' => 'joy', 'matches' => 0]);
});

test('every emotion is always present in the result', function (): void {
    expect(array_keys((new EmotionAnalyzer())->analyze('a happy cat')['emotions']))
        ->toBe(['joy', 'anger', 'sadness', 'fear', 'surprise', 'disgust']);
});

test('a custom lexicon replaces the default emotion groups', function (): void {
    $analyzer = new EmotionAnalyzer(['joy' => ['cat']]);

    expect($analyzer->analyze('a cat'))->toMatchArray(['dominant' => 'joy', 'matches' => 1])
        ->and($analyzer->analyze('a happy dog')['matches'])->toBe(0);
});
