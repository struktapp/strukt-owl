<?php

/** Coverage for outlier detection in numeric series. */

use Strukt\Owl\Anomaly\AnomalyDetector;

test('the z score method reports the mean and deviation of the series', function (): void {
    $result = (new AnomalyDetector())->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'zscore', 3.0);

    expect($result['method'])->toBe('zscore')
        ->and($result['mean'])->toBe(104.5)
        ->and($result['stddev'])->toBe(298.5101)
        ->and($result['threshold'])->toBe(3.0)
        ->and($result['anomalies'])->toBe([]);
});

test('a lower threshold surfaces the extreme point', function (): void {
    $result = (new AnomalyDetector())->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'zscore', 2.0);

    expect($result['anomalies'])->toHaveCount(1)
        ->and($result['anomalies'][0]['value'])->toBe(1000.0)
        ->and($result['anomalies'][0]['index'])->toBe(9)
        ->and($result['anomalies'][0]['z_score'])->toBeGreaterThan(2.0);
});

test('the interquartile method flags a far out value', function (): void {
    $result = (new AnomalyDetector())->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'iqr');

    expect($result['method'])->toBe('iqr')
        ->and($result['lower'])->toBe(-3.5)
        ->and($result['upper'])->toBe(14.5)
        ->and($result['anomalies'])->toBe([['index' => 9, 'value' => 1000.0]]);
});

test('quartiles of an even length series are interpolated between samples', function (): void {
    $result = (new AnomalyDetector())->detect(['10', '20', '30', '40', '50', '60', '70', '80'], 'iqr');

    expect($result['lower'])->toBe(-25.0)
        ->and($result['upper'])->toBe(115.0)
        ->and($result['anomalies'])->toBe([]);
});

test('the series is sorted before the quartiles are taken', function (): void {
    $scrambled = (new AnomalyDetector())->detect(['9', '3', '1000', '1', '7', '2', '8', '4', '6', '5'], 'iqr');
    $ordered = (new AnomalyDetector())->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'iqr');

    expect($scrambled['lower'])->toBe($ordered['lower'])
        ->and($scrambled['upper'])->toBe($ordered['upper']);
});

test('a constant series has no spread and reports no anomalies', function (): void {
    $result = (new AnomalyDetector())->detect(['5', '5', '5', '5'], 'zscore');

    expect($result['stddev'])->toBe(0.0)
        ->and($result['anomalies'])->toBe([]);
});

test('an empty series reports no anomalies', function (): void {
    expect((new AnomalyDetector())->detect([], 'zscore'))
        ->toBe(['method' => 'zscore', 'anomalies' => []]);
});

test('an unknown method is rejected', function (): void {
    (new AnomalyDetector())->detect([1, 2, 3], 'bogus');
})->throws(InvalidArgumentException::class, 'Unknown anomaly method [bogus]');
