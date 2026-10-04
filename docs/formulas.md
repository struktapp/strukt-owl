# The formulas

Everything Owl computes, in one place. Notation follows the source; each
section links to the full explanation in its topic page.

## Text (`Nlp\Text`, `Nlp\Stopwords`)

- Normalization chain: `mb_strtolower(trim(t))`, then
  `preg_replace('/[^\p{L}\p{N}\s\'\-]+/', ' ', t)`, then
  `preg_replace('/\s+/', ' ', t)`.
- Sentence split: `preg_split('/(?<=[.!?])\s+/', t)` with fragments of zero
  words dropped.
- Term frequency: `freq(w) = count of w in the token list`.
- Stop words: a fixed list (English + small Swahili set); removable via
  `Text::words($t, true)`.

## Sentiment (`Sentiment\SentimentAnalyzer`)

Lexical hit `w` with valence `v(w)`:

```
adjusted(w) = v(w) × (−0.74) ^ (# negators in prior 3 tokens)
                    × (1.25) ^ (# intensifiers in prior 3 tokens)

raw      = Σ adjusted(w) + sign·min(0.5, #'!' × 0.05)
compound = raw / √(raw² + 15)                          ∈ (−1, 1)

pos  = Σ of positive magnitudes          neg = Σ of |negative magnitudes|
tot  = pos + neg
positive = pos / tot                     (0 when tot = 0)
negative = neg / tot
neutral  = 1 − positive − negative

label: 'positive' if compound ≥ 0.05, 'negative' if ≤ −0.05, else 'neutral'
```

## Emotions (`Emotion\EmotionAnalyzer`)

For each of the six emotion groups `e` (happy, anger, sadness, fear, surprise,
disgust):

```
count(e) = number of tokens (stop words removed) matching the group lexicon
total    = Σ count(e)

share(e) = count(e) / total            (0.0 when total = 0)
dominant = argmax_e share(e)           (null when total = 0)
```

Shares sum to 1 across the groups.

## Keywords (`Nlp\KeywordExtractor`)

Sentence-level TF-IDF over terms with `|w| ≥ 3`:

```
N        = number of sentences (≥ 1)
doc(w)   = number of sentences containing w
freq(w)  = occurrences of w in the whole text

idf(w)  = ln( (N + 1) / (doc(w) + 1) )

score(w) = freq(w) × (idf(w) + 1)
```

Top `limit` terms by descending `score`.

## Summarization (`Nlp\Summarizer`)

Extractive ranking of sentence `s` with length `|s| ≥ 1`:

```
score(s) = (1/|s|) × Σ_{ w ∈ s } ln( 1 + freq(w) )
```

Take top `N` sentences (default 3), re-order by original index, join with
spaces.

## Language (`Language\LanguageDetector`)

Weighted marker profiles per language `l`:

```
score(l) = Σ_{ m ∈ markers(l) } weight(m) × substr_count(lower(t), m)

total       = Σ_l score(l)                    (0 when nothing matches)
language    = argmax_l score(l)
confidence  = max_l score(l) / total          (0 when total = 0)
```

## Similarity (`Similarity\Similarity`)

Terms are stop-word-stripped words; vectors are term frequencies over the
unioned vocabulary.

```
cosine(u, v)  = (u · v) / ( ‖u‖ ‖v‖ )          ‖u‖ = √(Σ u_i²)
jaccard(A, B) = |A ∩ B| / |A ∪ B|              (both empty → 1)
dice(A, B)    = 2|A ∩ B| / (|A| + |B|)         (both empty → 1)
levenshtein   = PHP levenshtein(lower(a), lower(b))   (distance)

jaro:
window      = ⌊max(|a|,|b|)/2⌋ − 1              (floored at 0)
m(jaro)     = chars of a and b matching inside the window
t           = half the transposition disagreements
jaro        = ( m/|a| + m/|b| + (m − t)/m ) / 3
prefix      = length of common prefix, capped at 4

jaroWinkler = jaro + prefix × 0.1 × (1 − jaro)
```

Special cases: identical strings → `1.0`; either empty → `0.0`.

## Classification (`Classifier\NaiveBayesClassifier`)

Multinomial naive Bayes, Laplace smoothing (`α = 1`).

Training per class `c`:
```
D(c)   += 1          per training document
n_c(w)   = raw token counts in class c (stop words removed)
N(c)     = Σ_w n_c(w)
V        = |shared vocabulary|
```

Prediction for tokens `w_1..w_k`:
```
prior(c)    = D(c) / Σ_c' D(c')
score(c)    = ln prior(c) + Σ_i ln( (n_c(w_i) + 1) / (N(c) + V) )

softmax (stable, subtract max):
p(c) = exp(score(c) − maxScore) / Σ_c' exp(score(c') − maxScore)

class      = argmax_c p(c)
confidence = p(class)                    (scores sum to 1)
```

Persistence: `export()` ↔ `import()` round-trips
`docs`, `counts`, `totals`, `vocab` losslessly.

## Spam (`Spam\SpamDetector`)

```
score = min( 1,
               0.12 × (# matched markers)
             + 0.04 × (# occurrences of '!')
             + 0.15 × (1 if http:// or https:// present else 0) )

label = 'spam'   if score ≥ 0.35
        'normal' otherwise

confidence = score         (spam)
             1 − score     (normal)
```

## Anomaly (`Anomaly\AnomalyDetector`)

Z-score method:
```
mean = (1/n) Σ x_i
var  = (1/n) Σ (x_i − mean)²
std  = √var
z(x) = (x − mean) / std
anomaly(x) ⟺ |z(x)| > threshold        (default 3.0; std = 0 → none)
```

IQR method (series sorted first):
```
Q1 = 25th percentile, Q3 = 75th percentile      linear interpolation at (n−1)·p
IQR  = Q3 − Q1
lower = Q1 − 1.5·IQR
upper = Q3 + 1.5·IQR
anomaly(x) ⟺ x < lower   or   x > upper
```

## Readability (`Nlp\Readability`)

```
sentences = sentence fragments (≥ 1 word)
words     = word tokens
syllables = Σ countSyllables(w)          heuristic vowel-group count, min 1

ASL = words / max(1, sentences)
ASW = syllables / words
reading_time = words / 200

Flesch Reading Ease  = 206.835 − 1.015·ASL − 84.6·ASW
Flesch-Kincaid Grade = 0.39·ASL + 11.8·ASW − 15.59
```

Syllable heuristic: strip non-letters and lowercase; ≤ 3 letters → 1; drop a
trailing silent `e` (`/(?:[^aeiouy]e)$/`); count `/[aeiouy]+/` groups, min 1.

All metric outputs are rounded to 2 decimal places; empty text yields zeros.