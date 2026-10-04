# Similarity

`Strukt\Owl\Similarity\Similarity` measures how alike two pieces of text are
with five complementary measures, all computed from the math primitives
(including a `Strukt\Matrix` product for the cosine).

```php
$sim = new Similarity();

$sim->cosine('hello world', 'hello world');        // 1.0
$sim->jaccard('cat dog', 'cat fish');              // 0.3333
$sim->dice('cat dog', 'cat fish');                 // 0.5
$sim->levenshtein('kitten', 'sitting');            // 3
$sim->jaroWinkler('MARHTA', 'MARTHA');             // 0.9611
```

## Cosine

Texts are reduced to term-frequency vectors over the union of their words
(stop words removed). The cosine of the angle between them is their similarity:

```
cos(θ) = (u · v) / ( ‖u‖ × ‖v‖ )

u · v   = Σ w_u,i × w_v,i
‖u‖     = √( Σ w_u,i² )
```

Two texts with no vocabulary at all score `0.0` (the denominator guards the
division). The dot product is computed with a `Strukt\Matrix` `dot` operation.

## Jaccard and Dice

Both operate on the sets of unique words:

```
J(A, B) = |A ∩ B| / |A ∪ B|

Dice(A, B) = 2|A ∩ B| / ( |A| + |B| )
```

When both sets are empty they score `1.0` (identical); when either is empty
they score `0.0`.

## Levenshtein

The classic edit distance (PHP `levenshtein()`) on the lowercased strings —
the minimum number of insertions, deletions, and substitutions needed to turn
one into the other. It is a distance, so lower means more similar.

## Jaro-Winkler

The Jaro similarity rewards matching characters inside a sliding window, then a
prefix bonus up-weights the result when the strings begin the same:

```
window   = ⌊max(|a|, |b|) / 2⌋ − 1          # floored at 0
jaro     = ( m/|a| + m/|b| + (m − t)/m ) / 3
prefix   = leading equal characters, capped at 4
jaroWinkler(a, b) = jaro + (1 − jaro) × prefix × 0.1

m  = matching characters inside the window
t  = half the number of transposition disagreements
```

Identical strings return `1.0`; either string empty returns `0.0`. Owl uses
this measure for the `match` command ([cli.md](cli.md#match)) because it tolerates
typos and case differences in short strings.

The `Cli\Command\MatchCommand` also reports a score, so a single measure can
pick the closest of many candidates.