Strukt Owl
===

Self-contained, explainable AI/NLP utilities for PHP 8.2+, built on the
[Strukt Math](../math) primitives.

No API keys, network services, Python, model server, or database are required.

## Install

```bash
composer require strukt/owl
```

The package installs a CLI executable:

```bash
vendor/bin/owl --help
```

## Commands

```text
sentiment       Analyze sentiment
emotion         Detect emotions
keywords        Extract keywords
summarize       Extractive TF-IDF-style summary
language        Detect language
similarity      Compare two texts
match           Find closest candidate
classify        Classify text with Naive Bayes
train           Train a Naive Bayes model
spam            Detect spam with a built-in heuristic
readability     Analyze readability
anomaly         Detect numeric anomalies
```

Every command supports `--json` where applicable.

## Examples

```bash
./vendor/bin/owl sentiment "This product is absolutely fantastic!"
./vendor/bin/owl emotion "I am so happy that we finally won!"
cat article.txt | ./vendor/bin/owl keywords --limit=10
cat article.txt | ./vendor/bin/owl summarize --sentences=5
./vendor/bin/owl language "Habari ya asubuhi, uko aje?"
./vendor/bin/owl similarity "Kenya Airways" "Kenya Airways Limited"
./vendor/bin/owl match "Kenya Airways Ltd" "Kenya Airways" "Kenya Power" "Safaricom"
cat article.txt | ./vendor/bin/owl readability
./vendor/bin/owl anomaly "1,2,3,4,5,6,7,8,9,1000" --method=iqr
```

Text can also be piped in, and read from a file with `--file`:

```bash
cat article.txt | vendor/bin/owl keywords
vendor/bin/owl sentiment --file message.txt
```

### Classifier

```bash
vendor/bin/owl train sports/*.txt --class=sports --model=model.json
vendor/bin/owl train business/*.txt --class=business --model=model.json
vendor/bin/owl classify article.txt --model=model.json
```

Training accumulates into an existing model file, so each call can add a
single class.

## PHP API

```php
use Strukt\Owl\Sentiment\SentimentAnalyzer;

$result = (new SentimentAnalyzer())->analyze('I absolutely love PHP!');
```

Other main classes:

```text
Strukt\Owl\Emotion\EmotionAnalyzer
Strukt\Owl\Similarity\Similarity
Strukt\Owl\Nlp\Text
Strukt\Owl\Nlp\KeywordExtractor
Strukt\Owl\Nlp\Summarizer
Strukt\Owl\Nlp\Readability
Strukt\Owl\Classifier\NaiveBayesClassifier
Strukt\Owl\Language\LanguageDetector
Strukt\Owl\Spam\SpamDetector
Strukt\Owl\Anomaly\AnomalyDetector
```

## Design

The package favors deterministic, inspectable algorithms over opaque remote AI
services. Every numeric step is expressed with the `Strukt\Math` primitives, so
each score is built from typed value objects rather than loose floating point
arithmetic, and the intermediate quantities are inspectable in the source.

It is suitable for CLI tools, PHP applications, offline processing, prototypes,
data cleaning, and lightweight NLP.

## Tests

```bash
composer test
composer lint
```

## License

MIT.
