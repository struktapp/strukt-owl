<?php

/** Coverage for the naive Bayes classifier. */

use Strukt\Owl\Classifier\NaiveBayesClassifier;

function trainedClassifier(): NaiveBayesClassifier
{
    $classifier = new NaiveBayesClassifier();
    $classifier->train('sports', [
        'football match goal team stadium',
        'the team scored a goal in the match',
        'basketball league championship team',
    ]);
    $classifier->train('business', [
        'quarterly revenue profit margin report',
        'the company reported revenue growth',
        'board approves profit forecast',
    ]);

    return $classifier;
}

test('an untrained classifier refuses to predict', function (): void {
    (new NaiveBayesClassifier())->predict('anything');
})->throws(LogicException::class, 'Classifier has not been trained.');

test('training text is classified into the matching class', function (): void {
    $classifier = trainedClassifier();

    expect($classifier->predict('the team scored another goal'))
        ->toMatchArray(['class' => 'sports', 'confidence' => 0.96]);
});

test('financial vocabulary is classified as business', function (): void {
    expect(trainedClassifier()->predict('revenue forecast and profit report'))
        ->toMatchArray(['class' => 'business', 'confidence' => 0.973]);
});

test('unrelated text still returns a class with a full score set', function (): void {
    $result = trainedClassifier()->predict('quantum banana');

    expect($result['class'])->toBeIn(['sports', 'business'])
        ->and(array_keys($result['scores']))->toBe(['sports', 'business'])
        ->and(array_sum($result['scores']))->toEqualWithDelta(1.0, 0.0001);
});

test('every class is scored rather than only the winner', function (): void {
    expect(trainedClassifier()->predict('the team scored another goal')['scores'])
        ->toBe(['sports' => 0.96, 'business' => 0.04]);
});

test('an exported model can be restored and predicts the same way', function (): void {
    $model = trainedClassifier()->export();
    $restored = NaiveBayesClassifier::import($model);

    expect($restored->predict('company profit growth'))
        ->toBe(trainedClassifier()->predict('company profit growth'))
        ->toMatchArray(['class' => 'business', 'confidence' => 0.9231]);
});

test('an exported model carries the counts needed to predict', function (): void {
    $model = trainedClassifier()->export();

    expect(array_keys($model))->toBe(['docs', 'counts', 'totals', 'vocab'])
        ->and($model['docs'])->toBe(['sports' => 3, 'business' => 3])
        ->and($model['vocab'])->toBeArray()
        ->and($model['totals']['sports'])->toBeGreaterThan(0);
});

test('an empty model payload is imported without an error', function (): void {
    expect(NaiveBayesClassifier::import([])->export())
        ->toBe(['docs' => [], 'counts' => [], 'totals' => [], 'vocab' => []]);
});

test('training can be repeated to build up a corpus', function (): void {
    $classifier = new NaiveBayesClassifier();
    $classifier->train('sports', ['goal team match']);
    $classifier->train('sports', ['stadium league']);

    expect($classifier->export()['docs'])->toBe(['sports' => 2])
        ->and($classifier->predict('the team scored a goal')['class'])->toBe('sports');
});
