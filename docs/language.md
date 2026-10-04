# Language detection

`Strukt\Owl\Language\LanguageDetector` guesses the language of a text from a
bank of weighted marker profiles. It is a frequency-of-markers model with no
training set, so it stays tiny and fast.

```php
$result = (new LanguageDetector())->detect('Bonjour tout le monde');

$result['language']    // 'fr'
$result['confidence']  // 0.44
$result['scores']      // ['en' => ..., 'fr' => ..., ...]
$result['total']       // sum of all raw scores
```

## Marker profiles

Seven languages are supported: `en`, `fr`, `de`, `es`, `it`, `pt`, `sw`.
Each language has a list of characteristic substrings (`the`, `and`, `of` for
English; `le`, `la`, `des` for French; `der`, `und` for German; ...).

Every marker also carries a weight — short function words weigh more than
longer stems because they are less ambiguous.

## Scoring

For each language `l`:

```
score(l) = Σ_{ marker m ∈ lang(l) } weight(m) × substr_count(lower(text), m)
```

Every occurrence in the lowercased text contributes the marker's weight; longer
texts naturally produce larger absolute scores.

## Result

- `scores` — every language's raw weighted sum.
- `total` — Σ of all scores (`0` when the text has no matches).
- `language` — the language with the highest score.
- `confidence` — winner divided by the total, rounded to 4 dp:

```
confidence = max(score(l)) / total
```

For example `Bonjour tout le monde` scores French highest, and the confidence
ticks up as the French markers form a larger share of all hits.

The command line equivalent is in [cli.md](cli.md#language).