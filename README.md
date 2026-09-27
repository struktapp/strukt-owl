Strukt Owl
===

Self-contained, explainable AI/NLP utilities for PHP 8.2+.

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
summarize       Extractive TextRank-style summary
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
/bin/owl sentiment "This product is absolutely fantastic!"
/bin/owl emotion "I am so happy that we finally won!"
/bin/owl keywords article.txt --limit=10
/bin/owl summarize article.txt --sentences=5
/bin/owl language "Habari ya asubuhi, uko aje?"
/bin/owl similarity "Kenya Airways" "Kenya Airways Limited"
/bin/owl match "Kenya Airways Ltd" "Kenya Airways" "Kenya Power" "Safaricom"
/bin/owl readability article.txt
/bin/owl anomaly values.txt --method=zscore
```

### Classifier

```bash
/bin/owl train --class=sports sports/*.txt --model=model.json
/bin/owl train --class=business business/*.txt --model=model.json
/bin/owl classify article.txt --model=model.json
```

## PHP API

```php
use Strukt\Owl\Sentiment\SentimentAnalyzer;

$result = (new SentimentAnalyzer())->analyze('I absolutely love PHP!');
```

Other main classes:

```text
Strukt\Owl\Emotion\EmotionAnalyzer
Strukt\Owl\Similarity\Similarity
Strukt\Owl\Nlp\KeywordExtractor
Strukt\Owl\Nlp\Summarizer
Strukt\Owl\Nlp\Readability
Strukt\Owl\Classifier\NaiveBayesClassifier
Strukt\Owl\Language\LanguageDetector
Strukt\Owl\Spam\SpamDetector
Strukt\Owl\Anomaly\AnomalyDetector
```

## Design

The package intentionally favors deterministic, inspectable algorithms over opaque remote AI services. It is suitable for CLI tools, PHP applications, offline processing, prototypes, data cleaning, and lightweight NLP.

## License

MIT.
