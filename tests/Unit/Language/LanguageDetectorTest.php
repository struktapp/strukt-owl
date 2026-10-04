<?php

/** Coverage for weighted language detection. */

use Strukt\Owl\Language\LanguageDetector;

test('english text is detected with its own markers', function (): void {
    $result = (new LanguageDetector())->detect('This product is absolutely fantastic! I love it.');

    expect($result['language'])->toBe('en')
        ->and($result['scores']['en'])->toBe(4)
        ->and($result['confidence'])->toBe(1.0);
});

test('markers are counted anywhere in the text, not just as whole words', function (): void {
    $result = (new LanguageDetector())->detect('The report was filed on Tuesday by the committee.');

    expect($result['language'])->toBe('en')
        ->and($result['scores']['en'])->toBe(16)
        ->and($result['scores']['sw'])->toBe(8)
        ->and($result['scores']['fr'])->toBe(0)
        ->and($result['scores']['it'])->toBe(0)
        ->and($result['confidence'])->toBe(0.4324);
});

test('scores are reported for every supported language', function (): void {
    expect(array_keys((new LanguageDetector())->detect('hello')['scores']))
        ->toBe(['en', 'fr', 'de', 'es', 'it', 'pt', 'sw']);
});

test('scores are ordered from the most to the least likely', function (): void {
    $scores = (new LanguageDetector())->detect('the quick brown fox and the dog')['scores'];
    $values = array_values($scores);
    $sorted = $values;
    rsort($sorted);

    expect($values)->toBe($sorted);
});

test('empty text reports no confidence instead of dividing by zero', function (): void {
    expect((new LanguageDetector())->detect(''))
        ->toMatchArray(['language' => 'en', 'confidence' => 0.0]);
});

test('detection is case insensitive', function (): void {
    $lower = (new LanguageDetector())->detect('the report was filed');
    $upper = (new LanguageDetector())->detect('THE REPORT WAS FILED');

    expect($upper['language'])->toBe($lower['language'])
        ->and($upper['confidence'])->toBe($lower['confidence']);
});

test('custom profiles replace the default language markers', function (): void {
    $detector = new LanguageDetector(['xx' => ['mambo' => 5]]);

    expect($detector->detect('mambo'))->toMatchArray(['language' => 'xx', 'confidence' => 1.0])
        ->and(array_keys($detector->detect('mambo')['scores']))->toBe(['xx'])
        ->and($detector->detect('the'))->toMatchArray(['language' => 'xx', 'confidence' => 0.0]);
});
