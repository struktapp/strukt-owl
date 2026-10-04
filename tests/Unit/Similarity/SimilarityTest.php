<?php

/** Coverage for string and term similarity measures. */

use Strukt\Owl\Similarity\Similarity;

test('identical texts are perfectly similar', function (): void {
    $similarity = new Similarity();

    expect($similarity->cosine('hello world', 'hello world'))->toBe(1.0)
        ->and($similarity->jaccard('hello world', 'hello world'))->toBe(1.0)
        ->and($similarity->dice('hello world', 'hello world'))->toBe(1.0)
        ->and($similarity->levenshtein('hello world', 'hello world'))->toBe(0)
        ->and($similarity->jaroWinkler('hello world', 'hello world'))->toBe(1.0);
});

test('a shared vocabulary gives a high cosine similarity', function (): void {
    expect((new Similarity())->cosine('Kenya Airways', 'Kenya Airways Limited'))
        ->toBe(0.8164965809277261);
});

test('term measures separate overlapping from unrelated texts', function (): void {
    $similarity = new Similarity();
    $overlapping = $similarity->jaccard('the cat sat on the mat', 'the cat was on the mat');
    $unrelated = $similarity->jaccard('the cat sat on the mat', 'quantum banana');

    expect($overlapping)->toBe(0.6666666666666666)
        ->and($unrelated)->toBe(0.0)
        ->and($similarity->dice('the cat sat on the mat', 'the cat was on the mat'))->toBe(0.8);
});

test('empty term sets are handled as a deliberate edge case', function (): void {
    $similarity = new Similarity();

    expect($similarity->cosine('', ''))->toBe(0.0)
        ->and($similarity->jaccard('', ''))->toBe(1.0)
        ->and($similarity->dice('', ''))->toBe(1.0)
        ->and($similarity->jaroWinkler('', ''))->toBe(1.0)
        ->and($similarity->jaroWinkler('abc', ''))->toBe(0.0);
});

test('edit distance counts single character edits', function (): void {
    $similarity = new Similarity();

    expect($similarity->levenshtein('abc', 'xyz'))->toBe(3)
        ->and($similarity->levenshtein('MARTHA', 'MARHTA'))->toBe(2)
        ->and($similarity->levenshtein('Hello', 'hello'))->toBe(0);
});

test('jaro winkler matches the published reference values', function (): void {
    $similarity = new Similarity();

    expect($similarity->jaroWinkler('MARTHA', 'MARHTA'))->toBe(0.9611111111111111)
        ->and($similarity->jaroWinkler('DWAY', 'DWAYA'))->toBe(0.96)
        ->and($similarity->jaroWinkler('DIXON', 'DICKSONX'))->toBe(0.8133333333333332)
        ->and($similarity->jaroWinkler('abc', 'xyz'))->toBe(0.0);
});

test('jaro winkler rewards a shared prefix', function (): void {
    $similarity = new Similarity();

    expect($similarity->jaroWinkler('program', 'programme'))
        ->toBeGreaterThan($similarity->jaroWinkler('program', 'reprogram'));
});

test('similarity is case insensitive', function (): void {
    $similarity = new Similarity();

    expect($similarity->jaroWinkler('HELLO', 'hello'))->toBe(1.0)
        ->and($similarity->levenshtein('HELLO', 'hello'))->toBe(0);
});
