# Keywords

`Strukt\Owl\Nlp\KeywordExtractor` ranks candidate words by a TF-IDF-style score
computed at the sentence level, then returns the top `N` terms in descending
score order.

```php
$keywords = (new KeywordExtractor())->extract(
    'The quick brown fox jumps over the lazy dog. The fox is quick.',
    3,
);
// ['fox' => ..., 'quick' => ..., 'brown' => ...]
```

## How a score is built

1. The text is split into sentences (`Text::sentences()`).
2. Each sentence is tokenized with stop words removed.
3. For every term of length ≥ 3:

   - `freq(w)` — how many times the term appears in the whole text;
   - `doc(w)` — in how many sentences the term appears.

The corpus size `N` is the number of sentences (never below 1). The inverse
document frequency is smoothed so every present term stays positive, and the
final score is term frequency times the smoothed inverse:

```
idf(w) = ln( (N + 1) / (doc(w) + 1) )

score(w) = freq(w) × (idf(w) + 1)
```

The `+ 1` inside the frequency factor is a Laplace-style smoothing: terms that
appear in only one sentence still receive their full `freq` weight instead of
collapsing to zero.

## Output

- Terms shorter than 3 characters are ignored.
- The limit is how many keywords to return (`extract($text, $limit)`).
- Results are returned most-important first.

The command line equivalent is in [cli.md](cli.md#keywords).