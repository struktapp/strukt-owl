# Readability

`Strukt\Owl\Nlp\Readability` scores text with two classic metrics — Flesch
Reading Ease and Flesch-Kincaid Grade Level — plus a reading time estimate.
All outputs are rounded to 2 decimal places.

```php
$result = (new Readability())->analyze('The quick brown fox jumps over the lazy dog.');

$result['words']           // 9
$result['sentences']       // 1
$result['syllables']       // 10
$result['reading_time']    // 0.05  minutes
$result['flesch']          // Flesch reading ease
$result['flesch_kincaid']  // Flesch-Kincaid grade level
```

## The metrics

First the derived quantities:

```
sentences = number of sentence fragments (with at least one word)
words     = total word tokens
syllables = Σ over words of syllable counts

ASL (average sentence length)     = words / max(1, sentences)
ASW (average syllables per word)  = syllables / words

reading_time = words / 200            # 200 words per minute
```

Then the two tried-and-tested formulas:

```
Flesch Reading Ease   = 206.835 − 1.015 × ASL − 84.6 × ASW
Flesch-Kincaid Grade  = 0.39 × ASL + 11.8 × ASW − 15.59
```

Higher Flesch means easier to read (school-teacher scale, roughly 90–100 for
young children up to 0–30 for dense academic prose). Higher grade means more
schooling needed to follow the text.

## Syllable counting

`countSyllables` is a heuristic, not a dictionary:

1. lowercases and strips non-letters;
2. words of ≤ 3 letters count as 1 syllable;
3. a silent trailing `e` is removed (`/(?:[^aeiouy]e)$/`), so `cake` → `cak`;
4. syllables = number of vowel groups (`/[aeiouy]+/`), minimimum 1.

This keeps the analyzer self-contained (no external syllable data), which
matches how every other Owl metric works.

Empty text yields all-zero readings rather than an error.