# Summarization

`Strukt\Owl\Nlp\Summarizer` is an extractive summarizer: it ranks existing
sentences and returns the top `N` in their original order.

```php
$summary = (new Summarizer())->summarize($article, 3);
// 'First sentence... Second sentence... Third sentence...'
```

## Ranking a sentence

Each sentence is scored independently:

1. Split the text into sentences (`Text::sentences()`).
2. Tokenize each sentence, removing stop words.
3. Build the corpus-wide term frequencies `freq(w)`.
4. For a sentence `s` with length `|s|` (word count, `≥ 1`):

```
score(s) = ( 1 / |s| ) × Σ_{ w ∈ s } ln( 1 + freq(w) )
```

The logarithm compresses high-frequency terms, so a word appearing many times
worldwide does not completely dominate a sentence. Dividing by sentence length
keeps long filler sentences from winning.

## Choosing the summary

- Sentences are sorted by descending score.
- The top `N` (`summarize($text, $sentences = 3)`) are kept.
- They are re-ordered by original position and joined with a space, producing
  a readable extract instead of a shuffled list.

Results are deterministic given the same text, because the ranking is a pure
function of the term frequencies.