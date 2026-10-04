<?php

/** Coverage for extractive summarisation. */

use Strukt\Owl\Nlp\Summarizer;

test('a summary keeps the requested number of sentences', function (): void {
    $summary = (new Summarizer())->summarize(article(), 3);

    expect(substr_count($summary, '.'))->toBeGreaterThan(0)
        ->and($summary)->toContain('noise')
        ->and($summary)->toStartWith('George Bodo | 10th April 2018');
});

test('short text is returned untouched because it already fits', function (): void {
    expect((new Summarizer())->summarize('One. Two.', 5))->toBe('One. Two.');
});

test('a zero sentence request returns an empty summary', function (): void {
    expect((new Summarizer())->summarize(article(), 0))->toBe('');
});

test('empty text summarises to an empty string', function (): void {
    expect((new Summarizer())->summarize(''))->toBe('');
});

test('chosen sentences are returned in document order', function (): void {
    $text = 'The cat sat quietly. Dogs bark loudly at strangers. The mat was bright red. Birds fly south each winter.';

    expect((new Summarizer())->summarize($text, 2))
        ->toBe('Dogs bark loudly at strangers. Birds fly south each winter.');
});
