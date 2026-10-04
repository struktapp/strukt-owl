# Command line

`strukt/owl` ships an `owl` executable registered in `composer.json`'s `bin`.
Run it from a console:

```
./vendor/bin/owl <command> [arguments] [options]
```

With no arguments (or `--help` / `-h`), it lists every command and its
description.

## Input conventions

Most NLP commands accept text the same way, so the pattern is worth learning
once:

- A positional `text` argument (quote it), and/or
- `--file=<path>` to read the text from a file, and/or
- piped standard input: `cat article.txt | ./vendor/bin/owl keywords`.

Every command that consumes text resolves it `--file` first, then the argument,
then stdin, so any of the three works. Every command accepts `--json` as a flag:
without it a plain-text report is printed (banner line, then `key: value`
pairs), with it the result is pretty-printed JSON (`JSON_PRETTY_PRINT` +
`JSON_UNESCAPED_UNICODE`).

Successful commands exit `0`. A missing required argument/option (for example
`similarity` with one text, or `train` without `--class`) exits `1`.

## Commands

### sentiment

```
./vendor/bin/owl sentiment "This product is absolutely fantastic! I love it." --json
```

Runs `SentimentAnalyzer::analyze()`. JSON: `label`, `compound`, `positive`,
`negative`, `neutral`. See [sentiment.md](sentiment.md).

### emotion

```
./vendor/bin/owl emotion "I am so happy and thrilled!" --json
```

Runs `EmotionAnalyzer::analyze()`. JSON: `emotions` (shares per emotion),
`dominant`, `matches`. See [emotion.md](emotion.md).

### keywords

```
cat article.txt | ./vendor/bin/owl keywords --limit=10 --json
```

Extracts the top keywords. `--limit` (int, default `10`). JSON:
`keywords` (term → score, most important first). See [keywords.md](keywords.md).

### summarize

```
./vendor/bin/owl summarize "One. Two two! Three? Four" --sentences=2 --json
```

Extractive summary. `--sentences` (int, default `3`). JSON: `summary`.
See [summarize.md](summarize.md).

### language

```
./vendor/bin/owl language "Bonjour tout le monde" --json
```

Language detection. JSON: `language`, `confidence`, `scores`, `total`.
See [language.md](language.md).

### similarity

```
./vendor/bin/owl similarity "hello world" "hello world" --json
```

Requires two positional texts. JSON contains all five measures: `cosine`,
`jaccard`, `dice`, `levenshtein`, `jaro_winkler`.
See [similarity.md](similarity.md).

### match

```
./vendor/bin/owl match "marhta" "MARHTA banana" --json
```

Finds the candidate closest to a query using Jaro-Winkler. `candidates` are
split on whitespace/commas/semicolons. JSON: `query`, `match`, `score`
(rounded to 4 dp), `algorithm` (`jaro-winkler`). It throws if no candidates
are given.

### train and classify

The classifier persists as a JSON model file (default `model.json`):

```
./vendor/bin/owl train --class=sports sports1.txt sports2.txt --model=my-model.json
./vendor/bin/owl train --class=business biz1.txt biz2.txt --model=my-model.json
./vendor/bin/owl classify "the team scored a goal" --model=my-model.json --json
```

`train` requires `--class` (string). Each positional argument is a file path
(`is_file`); every existing file is read as a training document. If the model
file already exists it is loaded first, so repeated runs accumulate a corpus —
mirroring `NaiveBayesClassifier::train()` on the same object. The model is
saved with `JSON_PRETTY_PRINT`.

`train` JSON: `model` (path), `class`, `documents` (count read).
`classify` JSON: `class`, `confidence`, `scores`. Model files are decoded with
`JSON_THROW_ON_ERROR`. See [classifier.md](classifier.md).

### spam

```
./vendor/bin/owl spam "Buy now!!! Free money, click here: https://example.com" --json
```

JSON: `label`, `score`, `confidence`. See [spam.md](spam.md).

### readability

```
./vendor/bin/owl readability "The quick brown fox jumps over the lazy dog." --json
```

JSON: `words`, `sentences`, `syllables`, `reading_time`, `flesch`,
`flesch_kincaid`. See [readability.md](readability.md).

### anomaly

```
./vendor/bin/owl anomaly "1,2,3,4,5,6,7,8,9,1000" --method=iqr --json
```

Values are split on whitespace/commas/semicolons (`--file` also accepted).
Options: `--method` (`zscore` default, `iqr`), `--threshold` (float, default
`3`, used by zscore). JSON depends on the method — see
[anomaly.md](anomaly.md).

### version

```
./vendor/bin/owl version --json
```

Reports the package release as a string. JSON: `version` (`0.1.0`).

## Pipeline examples

```
echo "The quick brown fox jumps over the lazy dog. The fox is quick." | ./vendor/bin/owl keywords --limit=3 --json
./vendor/bin/owl anomaly "$(cat readings.txt)" --method=zscore --threshold=2.5
```

Every command is also usable as a library — the CLI classes are thin wrappers
around the analyzer classes documented in the other topics.