# Sentiment

`Strukt\Owl\Sentiment\SentimentAnalyzer` assigns a text a positive/negative
label and a normalized `compound` score in `[-1, 1]` (VADER-style), using a
valence lexicon and a small set of modifier rules. It is fully offline and the
whole pipeline is visible.

```php
$result = (new SentimentAnalyzer())->analyze('This product is absolutely fantastic! I love it.');

$result['label']     // 'positive'
$result['compound']  // 0.8765
$result['positive']  // 1.0
$result['negative']  // 0.0
$result['neutral']   // 0.0
```

## Lexicon

`__construct(?array $lexicon = null, ?array $negators = null, ?array $intensifiers = null)`
accepts replacement lists. Passing none uses the defaults:

- A positive vocabulary (`amazing`, `brilliant`, `fantastic`, `great`, ...) with
  valences around `1.8`–`3.2`.
- A negative vocabulary (`awful`, `broken`, `hate`, `terrible`, ...) with
  valences around `-1.8` to `-3.0`.
- Negators: `not`, `never`, `no`, `neither`, ...
- Intensifiers: `absolutely`, `really`, `very`, ...

```php
$analyzer = new SentimentAnalyzer(['cat' => 2.0, 'storm' => -2.0]);
$analyzer->analyze('the cat sat')['label'];   // 'positive'
$analyzer->analyze('the storm came')['label'];// 'negative'
```

A custom lexicon replaces the default — `fantastic` no longer scores anything.

## Scoring

Tokens are `\w+` sequences plus standalone `!`/`?`, taken from the raw text.
For each lexicon hit:

1. Raw valence is read from the lexicon (`v`).
2. The previous three tokens are scanned for modifiers:
   - each negator multiplies the score by `-0.74`,
   - each intensifier multiplies the score by `1.25`.
3. The adjusted valence is added to the running raw score.

So `I do not love this` inverts `love`, and `very good` amplifies `good`.

Emphasis from exclamation marks adds to the raw magnitude:

```
emphasis = min(0.5, exclaim × 0.05)          # exclaim = count of '!'
```

The emphasis joins the raw score with the sign of the raw sum, then everything

The emphasis joins the raw score with the sign of the raw sum, then everything
is normalized into the compound scale:

```
compound = raw / √(raw² + 15)
```

This keeps every output in `(-1, 1)` regardless of text length.

## Shares and label

Positive and negative contributions are summed separately; their magnitudes
are converted to shares so the three always add to `1`:

```
total    = positives + negatives            # rounded to 4 dp
positive = positives / total                # 0 when total is 0
negative = |negatives| / total
neutral  = 1 − positive − negative

label = 'positive'   if compound ≥  0.05
        'negative'   if compound ≤ −0.05
        'neutral'    otherwise
```

Empty text is neutral (positive `0.0`, negative `0.0`, neutral `1.0`,
compound `0.0`), never an error.

See the full equations in [formulas.md](formulas.md#sentiment).