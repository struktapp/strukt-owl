# Strukt Owl documentation

Self-contained, explainable AI/NLP utilities for PHP 8.2+, built on the
`Strukt\Math` primitives. No API keys, network services, Python, model server,
or database are required.

Every algorithm is deterministic and inspectable: each score is built from the
math primitives (`number()`, `pipeline()`, `monad()`, `Matrix`) as typed value
objects, and the intermediate quantities are visible in the source.

Topics:

- [NLP foundations](text.md) — normalization, tokenization, sentences, stop words.
- [Sentiment](sentiment.md) — lexicon-based valence scoring.
- [Emotions](emotion.md) — labelled word-group shares.
- [Keywords](keywords.md) — TF-IDF-style term scoring.
- [Summarization](summarize.md) — extractive sentence ranking.
- [Language detection](language.md) — weighted marker profiles.
- [Similarity](similarity.md) — cosine, Jaccard, Dice, Levenshtein, Jaro-Winkler.
- [Classification](classifier.md) — multinomial naive Bayes with smoothing.
- [Spam detection](spam.md) — a small weighted heuristic.
- [Anomaly detection](anomaly.md) — z-score and IQR fences.
- [Readability](readability.md) — Flesch and Flesch-Kincaid metrics.
- [Command line](cli.md) — every `owl` command.
- [The formulas](formulas.md) — every quantity Owl computes, in one place.