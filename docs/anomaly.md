# Anomaly detection

`Strukt\Owl\Anomaly\AnomalyDetector` flags unusual values in a numeric series
with one of two methods, `zscore` or `iqr`. Both return the statics that drove
the decision, so the output is explainable rather than a bare list.

```php
$detector = new AnomalyDetector();

$detector->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'zscore', 2.0);
// ['method' => 'zscore', 'mean' => 104.5, 'stddev' => 298.5101, 'threshold' => 2.0,
//  'anomalies' => [['index' => 9, 'value' => 1000.0, 'z_score' => ...]]]

$detector->detect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '1000'], 'iqr');
// ['method' => 'iqr', 'lower' => -3.5, 'upper' => 14.5,
//  'anomalies' => [['index' => 9, 'value' => 1000.0]]]
```

## Z-score method

Values are treated as a sample of the series:

```
mean  = (1/n) Σ x_i
var   = (1/n) Σ (x_i − mean)²
std   = √var

z(x)  = (x − mean) / std
```

A point is an anomaly when `|z(x)| > threshold` (default `3.0`). Each anomaly
carries its index, value, and `z_score`. A constant series has `stddev 0.0` and
reports no anomalies (the division is guarded).

The report for this method: `method`, `mean`, `stddev`, `threshold`,
`anomalies`.

## IQR method

The interquartile range is robust to outliers by construction — the fences are
built from quartiles, so extreme points influence the decision only through
their position relative to them:

```
sort(x)

Q1 = 25th percentile, Q3 = 75th percentile   (linear interpolation)
IQR      = Q3 − Q1
lower    = Q1 − 1.5 × IQR
upper    = Q3 + 1.5 × IQR
```

A point is an anomaly when it falls outside `[lower, upper]`. Percentiles are
interpolated at position `(n − 1) × p` between the two neighboring samples, so
even-length series behave correctly (see the test coverage).

The report for this method: `method`, `lower`, `upper`, `anomalies`.

## Empty and unknown input

- An empty series reports `['method' => ..., 'anomalies' => []]`.
- An unknown method throws `InvalidArgumentException`
  (`Unknown anomaly method [bogus].`).

See [cli.md](cli.md#anomaly) for the command line front end.