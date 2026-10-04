# NLP foundations

`Strukt\Owl\Nlp\Text` is the text normalization and tokenization layer shared
by every analyzer in Owl. All normalization rules live in one place so keyword
extraction, classification, and similarity scoring stay consistent.

`Strukt\Owl\Nlp\Stopwords` supplies the default stop list.

## Normalize

`Text::normalize()` lowercases and trims text, then strips punctuation in three
stages (a monad chain from the math primitives):

1. `mb_strtolower(trim($text))`
2. replace everything that is not a letter, digit, whitespace, apostrophe, or
   hyphen with a space: `[^\p{L}\p{N}\s'\-]+`
3. collapse runs of whitespace into a single space: `\s+`

Apostrophes and hyphens survive, so `Kenya's` and `e-commerce` stay single
tokens.

```php
Text::normalize("  Hello, Kenya's E-COMMERCE!  ");
// "hello kenya's e-commerce"
```

## Words

`Text::words($text, $removeStop = false, Stopwords $stopwords = ...)` runs a
positional pipeline: normalize, tokenize, and — when asked — drop stop words.

The tokenizer matches words with optional internal apostrophes or hyphens:

```
[\p{L}\p{N}]+(?:['\-][\p{L}\p{N}]+)*
```

```php
Text::words("Kenya's e-commerce boom.", true);
// ['kenya's', 'e-commerce', 'boom']
```

## Sentences

`Text::sentences()` splits on punctuation followed by whitespace
(`(?<=[.!?])\s+`) and drops fragments without any word token, so trailing
punctuation does not produce empty sentences:

```php
Text::sentences("One. Two two! Three? Four");
// ['One.', 'Two two!', 'Three?', 'Four']
```

## Term frequencies

`Text::termFrequencies($words)` counts tokens keyed by term:

```php
Text::termFrequencies(['fox', 'fox', 'dog']);
// ['fox' => 2, 'dog' => 1]
```

## Stop words

`Strukt\Owl\Nlp\Stopwords` mixes English function words with a small Swahili
set — enough to keep keyword and summary scores from being dominated by filler
in the two languages Owl is used with most.

```php
$stopwords = new Stopwords();
$stopwords->contains('the');   // true
$stopwords->contains('strukt'); // false
$stopwords->all();             // the list, in declaration order
```

Every constructor accepts a replacement list, which replaces the default —
the same pattern `SentimentAnalyzer`, `EmotionAnalyzer`, and the other
analyzers use for their lexicons.