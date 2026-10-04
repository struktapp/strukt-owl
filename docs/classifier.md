# Classification

`Strukt\Owl\Classifier\NaiveBayesClassifier` is a multinomial naive Bayes
classifier with Laplace (add-one) smoothing. It trains incrementally from
categorized documents, classifies new text into one of the seen classes, and
can be exported to / imported from a plain JSON model file.

```php
$classifier = new NaiveBayesClassifier();

$classifier->train('sports', [
    'football match goal team stadium',
    'the team scored a goal in the match',
]);
$classifier->train('business', [
    'quarterly revenue profit margin report',
    'the company reported revenue growth',
]);

$classifier->predict('the team scored another goal');
// ['class' => 'sports', 'confidence' => 0.96, 'scores' => ['sports' => 0.96, 'business' => 0.04]]
```

## Training

`train(string $class, array $documents)` accumulates counts: the document
count per class `D(c)`, token counts per class `n_c(w)`, the class totals
`N(c)`, and the shared vocabulary `V`. Training can be repeated for the same
class to grow the corpus incrementally.

```
D(c)  += 1                      per document of class c
n_c(w)  =  raw occurrences of w in class c documents
N(c)   =  Σ_w n_c(w)            total tokens in class c
```

Tokens are words with stop words removed, so filler like `the` never pollutes
the counts.

## Prediction

For a new text with tokens `w_1..w_k`, the classifier scores every class with
the log-posterior under the multinomial model. The class prior is the share of
training documents in that class:

```
prior(c)       = D(c) / Σ_c D(c)

logScore(c)    = ln prior(c)  +  Σ_i ln( (n_c(w_i) + 1) / (N(c) + V) )

Laplace smoothing (α = 1) keeps unseen words from zeroing a class.
```

The per-class log-scores are turned into probabilities with a softmax, so the
reported `scores` always sum to `1.0`:

```
p(c) = exp(logScore(c) − maxScore) / Σ_c' exp(logScore(c') − maxScore)
```

The winner is the largest probability; `confidence` is the winner's `p(c)`:

```
class      = argmax_c p(c)
confidence = p(class)
```

Two practical details:

- A classifier that has never been trained throws `LogicException`
  (`Classifier has not been trained.`) instead of guessing.
- Unknown vocabulary is handled by the `+ 1` smoothing, and every class is
  always scored — even unrelated text returns a full, normalized score set.

## Persistence

`export(): array` returns `['docs', 'counts', 'totals', 'vocab']` and
`import(array $model)` restores a classifier from it. The round-trip is
lossless — an imported model predicts identically to the original, and an empty
payload imports cleanly.

The command line workflow ([cli.md](cli.md#train-and-classify)) uses this exact
shape for its `model.json` files.