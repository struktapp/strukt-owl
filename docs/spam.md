# Spam detection

`Strukt\Owl\Spam\SpamDetector` decides whether a text looks like spam using a
small weighted heuristic. It is transparent: every term in the score has an
explicit weight, so you can see what pushed a message over the line.

```php
$result = (new SpamDetector())->analyze('Buy now!!! Free money, click here: https://example.com');

$result['label']       // 'spam'
$result['score']       // 1.0
$result['confidence']  // 1.0
```

## Score parts

The score accumulates three weighted ingredients:

| Ingredient                                   | Weight            |
|----------------------------------------------|-------------------|
| each matched marker (`free`, `win`, `click`, ...) | `0.12` each  |
| each `!`                                     | `0.04` each       |
| a hyperlink (`http://` or `https://`)        | `0.15` once       |

Marker matches are substring checks on the lowercased text; the link check and
exclamation counting use the raw text (`substr_count` on `!`). The score is
capped at `1`:

```
score = min( 1, 0.12 × matchedMarkers + 0.04 × (number of '!') + 0.15 × hasLink )
```

## Decision

A threshold of `0.35` separates the two labels:

```
label = 'spam'   if score ≥ 0.35
        'normal' otherwise

confidence = score          when spam
             1 − score      when normal
```

That `1 − score` for normal text means "how sure we are it is not spam" — a
message with zero signals reports confidence `1.0` for `normal`.

There are no parameters to tune in the public API; to change sensitivity,
adjust the weights in the source.