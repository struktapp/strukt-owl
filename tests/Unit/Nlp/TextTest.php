<?php

/** Coverage for text normalisation, tokenising, and sentence splitting. */

use Strukt\Owl\Nlp\Text;

test('normalisation lowercases the text and drops punctuation', function (): void {
    expect(Text::normalize("  Hello,  WORLD!! Kenya's best café  "))
        ->toBe("hello world kenya's best café");
});

test('normalisation leaves an already clean string untouched', function (): void {
    expect(Text::normalize('the quick brown fox'))->toBe('the quick brown fox');
});

test('normalisation keeps internal apostrophes and hyphens in one piece', function (): void {
    expect(Text::normalize("Kenya's e-commerce store"))->toBe("kenya's e-commerce store");
});

test('words keeps stop words by default and drops them on request', function (): void {
    $sentence = 'The quick brown fox and the lazy dog';

    expect(Text::words($sentence))->toBe(['the', 'quick', 'brown', 'fox', 'and', 'the', 'lazy', 'dog'])
        ->and(Text::words($sentence, true))->toBe(['quick', 'brown', 'fox', 'lazy', 'dog']);
});

test('words tokenises bare digits', function (): void {
    expect(Text::words('1234 !!! ...'))->toBe(['1234']);
});

test('words keeps accented words intact', function (): void {
    expect(Text::words('The café résumé', true))->toBe(['café', 'résumé']);
});

test('sentences splits on terminal punctuation', function (): void {
    expect(Text::sentences('One. Two two! Three?  Four'))->toBe(['One.', 'Two two!', 'Three?', 'Four']);
});

test('sentences returns a single unit when there is no terminal punctuation', function (): void {
    expect(Text::sentences('no punctuation here'))->toBe(['no punctuation here']);
});

test('sentences discards fragments that hold no words', function (): void {
    expect(Text::sentences('One. !!! ?? Two.'))->toBe(['One.', 'Two.']);
});

test('term frequencies count repeats across the token list', function (): void {
    expect(Text::termFrequencies(['the', 'cat', 'the', 'mat', 'the']))
        ->toBe(['the' => 3, 'cat' => 1, 'mat' => 1]);
});
