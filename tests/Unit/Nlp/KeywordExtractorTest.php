<?php

/** Coverage for TF-IDF style keyword extraction. */

use Strukt\Owl\Nlp\KeywordExtractor;

test('keywords are ranked by score with the strongest first', function (): void {
    $keywords = (new KeywordExtractor())->extract(article(), 3);

    expect($keywords)->toBe([2011, 'economic', 'all']);
});

test('a longer limit returns more of the ranked list', function (): void {
    $extractor = new KeywordExtractor();
    $ten = $extractor->extract(article(), 10);
    $three = $extractor->extract(article(), 3);

    expect($ten)->toHaveCount(10)
        ->and(array_slice($ten, 0, 3))->toBe($three);
});

test('short tokens are never treated as keywords', function (): void {
    $keywords = (new KeywordExtractor())->extract('The cat sat on a mat in the sun.', 10);

    expect($keywords)->not->toContain('cat', 'sat', 'mat', 'the');
});

test('a term repeated in one sentence is counted once as a document', function (): void {
    $extractor = new KeywordExtractor();
    $repeated = $extractor->extract('alpha alpha alpha. beta beta.', 2);
    $spread = $extractor->extract('alpha. beta.', 2);

    expect($repeated)->toBe(['alpha', 'beta'])
        ->and($spread)->toBe(['alpha', 'beta']);
});

test('extraction returns nothing for empty text or a zero limit', function (): void {
    expect((new KeywordExtractor())->extract(''))->toBe([])
        ->and((new KeywordExtractor())->extract('a full sentence of words here', 0))->toBe([]);
});
