<?php

/** Coverage for lexicon based sentiment scoring. */

use Strukt\Owl\Sentiment\SentimentAnalyzer;

test('enthusiastic text is reported as positive', function (): void {
    $result = (new SentimentAnalyzer())->analyze('This product is absolutely fantastic! I love it.');

    expect($result['label'])->toBe('positive')
        ->and($result['positive'])->toBe(1.0)
        ->and($result['negative'])->toBe(0.0)
        ->and($result['neutral'])->toBe(0.0)
        ->and($result['compound'])->toBe(0.8765);
});

test('hostile text is reported as negative', function (): void {
    $result = (new SentimentAnalyzer())->analyze('This is terrible and broken, I hate it!!!');

    expect($result['label'])->toBe('negative')
        ->and($result['negative'])->toBe(1.0)
        ->and($result['positive'])->toBe(0.0)
        ->and($result['compound'])->toBe(-0.9109);
});

test('text without sentiment words is neutral', function (): void {
    $result = (new SentimentAnalyzer())->analyze('The report was filed on Tuesday by the committee.');

    expect($result['label'])->toBe('neutral')
        ->and($result['neutral'])->toBe(1.0)
        ->and($result['compound'])->toBe(0.0);
});

test('empty text is neutral rather than an error', function (): void {
    expect((new SentimentAnalyzer())->analyze(''))
        ->toMatchArray(['label' => 'neutral', 'positive' => 0.0, 'negative' => 0.0, 'neutral' => 1.0]);
});

test('a negator inverts the sentiment of the word it precedes', function (): void {
    $affirmative = (new SentimentAnalyzer())->analyze('I love this');
    $negated = (new SentimentAnalyzer())->analyze('I do not love this');

    expect($affirmative['compound'])->toBeGreaterThan(0.0)
        ->and($negated['compound'])->toBeLessThan(0.0)
        ->and($negated['label'])->toBe('negative');
});

test('an intensifier raises the magnitude of a positive score', function (): void {
    $plain = (new SentimentAnalyzer())->analyze('this is good');
    $intense = (new SentimentAnalyzer())->analyze('this is very good');

    expect($intense['compound'])->toBeGreaterThan($plain['compound']);
});

test('the compound score always stays inside the normalised range', function (): void {
    $analyzer = new SentimentAnalyzer();

    foreach (['awesome brilliant perfect', 'awful horrible worst', 'good and great and nice'] as $text) {
        $compound = $analyzer->analyze($text)['compound'];

        expect($compound)->toBeGreaterThanOrEqual(-1.0)
            ->and($compound)->toBeLessThanOrEqual(1.0);
    }
});

test('a custom lexicon replaces the default vocabulary', function (): void {
    $analyzer = new SentimentAnalyzer(['cat' => 2.0, 'storm' => -2.0]);

    expect($analyzer->analyze('the cat sat')['label'])->toBe('positive')
        ->and($analyzer->analyze('the storm came')['label'])->toBe('negative')
        ->and($analyzer->analyze('fantastic')['label'])->toBe('neutral');
});

test('custom negators and intensifiers replace the default lists', function (): void {
    $negated = new SentimentAnalyzer(['nice' => 2.0], ['nope'], []);
    $amplified = new SentimentAnalyzer(['nice' => 2.0], [], ['super']);

    expect($negated->analyze('nope nice')['label'])->toBe('negative')
        ->and($negated->analyze('never nice')['label'])->toBe('positive')
        ->and($amplified->analyze('super nice')['compound'])->toBeGreaterThan($amplified->analyze('nice')['compound'])
        ->and($amplified->analyze('very nice')['compound'])->toBe($amplified->analyze('nice')['compound']);
});
