<?php

/** Coverage for the spam heuristic. */

use Strukt\Owl\Spam\SpamDetector;

test('ordinary text is reported as normal', function (): void {
    $result = (new SpamDetector())->analyze('The report was filed on Tuesday by the committee.');

    expect($result['label'])->toBe('normal')
        ->and($result['score'])->toBe(0.0)
        ->and($result['confidence'])->toBe(1.0)
        ->and($result['matches'])->toBe([]);
});

test('promotional markers push the message over the threshold', function (): void {
    $result = (new SpamDetector())->analyze('Claim your free prize winner, click now for cash!');

    expect($result['label'])->toBe('spam')
        ->and($result['matches'])->toContain('claim', 'free', 'prize', 'winner', 'click', 'cash')
        ->and($result['score'])->toBeGreaterThan(0.35)
        ->and($result['confidence'])->toBe($result['score']);
});

test('a bare link adds weight on its own', function (): void {
    $result = (new SpamDetector())->analyze('visit https://example.com for details');

    expect($result['score'])->toBe(0.15)
        ->and($result['label'])->toBe('normal');
});

test('exclamation marks add weight on their own', function (): void {
    expect((new SpamDetector())->analyze('This is great!')['score'])->toBe(0.04)
        ->and((new SpamDetector())->analyze('This is great!!!')['score'])->toBe(0.12);
});

test('the score never exceeds one however many markers are present', function (): void {
    $result = (new SpamDetector())->analyze(
        'winner won prize free claim urgent congratulations cash money offer click bonus guaranteed '
        . 'limited time free cash prize winner https://example.com!!!!!',
    );

    expect($result['score'])->toBe(1.0)
        ->and($result['label'])->toBe('spam');
});

test('empty text is normal with full confidence', function (): void {
    expect((new SpamDetector())->analyze(''))
        ->toBe(['label' => 'normal', 'confidence' => 1.0, 'score' => 0.0, 'matches' => []]);
});

test('marker matching is case insensitive', function (): void {
    expect((new SpamDetector())->analyze('CLAIM YOUR FREE PRIZE')['matches'])
        ->toBe((new SpamDetector())->analyze('claim your free prize')['matches']);
});

test('custom markers replace the defaults and carry their own weights', function (): void {
    $detector = new SpamDetector(['urgent' => 0.3, 'free' => 0.1]);

    expect($detector->analyze('this is urgent')['matches'])->toBe(['urgent'])
        ->and($detector->analyze('this is urgent')['score'])->toBe(0.3)
        ->and($detector->analyze('free and urgent')['score'])->toBe(0.4)
        ->and($detector->analyze('free and urgent')['label'])->toBe('spam')
        ->and($detector->analyze('claim your prize')['matches'])->toBe([])
        ->and($detector->analyze('claim your prize')['score'])->toBe(0.0);
});
