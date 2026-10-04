<?php

/** Coverage for readability metrics. */

use Strukt\Owl\Nlp\Readability;

test('readability metrics are computed for a short sentence', function (): void {
    $result = (new Readability())->analyze('The report was filed on Tuesday by the committee.');

    expect($result['words'])->toBe(9)
        ->and($result['sentences'])->toBe(1)
        ->and($result['syllables'])->toBe(14)
        ->and($result['reading_time_minutes'])->toBe(0.05)
        ->and($result['flesch_reading_ease'])->toBe(66.1)
        ->and($result['flesch_kincaid_grade'])->toBe(6.28);
});

test('reading time grows with the length of the text', function (): void {
    $short = (new Readability())->analyze('The cat sat on the mat.');
    $long = (new Readability())->analyze(article());

    expect($long['words'])->toBe(290)
        ->and($long['sentences'])->toBe(13)
        ->and($long['reading_time_minutes'])->toBe(1.45)
        ->and($long['reading_time_minutes'])->toBeGreaterThan($short['reading_time_minutes']);
});

test('empty text reports zero for every metric', function (): void {
    expect((new Readability())->analyze(''))->toBe([
        'words' => 0,
        'sentences' => 0,
        'syllables' => 0,
        'reading_time_minutes' => 0.0,
        'flesch_reading_ease' => 0.0,
        'flesch_kincaid_grade' => 0.0,
    ]);
});

test('every metric is reported as a float so callers can divide directly', function (): void {
    $result = (new Readability())->analyze('The cat sat on the mat.');

    expect($result['reading_time_minutes'])->toBeFloat()
        ->and($result['flesch_reading_ease'])->toBeFloat()
        ->and($result['flesch_kincaid_grade'])->toBeFloat();
});
