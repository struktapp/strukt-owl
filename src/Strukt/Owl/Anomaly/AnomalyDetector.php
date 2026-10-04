<?php

declare(strict_types=1);

namespace Strukt\Owl\Anomaly;

use Strukt\Number;

/**
 * Flags outliers in a numeric series.
 *
 * Two methods are supported. The z-score method reports how many standard
 * deviations each point sits from the mean, while the IQR method fences the
 * middle half of the data and flags anything outside it. Every quantity is
 * derived with the math primitives so the two methods stay consistent.
 */
final class AnomalyDetector
{
    /**
     * Multiplier applied to the interquartile range to build the fences.
     */
    private const IQR_FACTOR = 1.5;

    /**
     * Detects anomalies in a numeric series.
     *
     * @param array<int, mixed> $values Numeric values, typically read from input.
     * @param string $method Either zscore or iqr.
     * @param float $threshold Absolute z-score above which a point is an anomaly.
     * @return array<string, mixed> Method statistics and the anomalies found.
     *
     * @throws \InvalidArgumentException When the method is unknown.
     */
    public function detect(array $values, string $method = 'zscore', float $threshold = 3.0): array
    {
        $series = array_map(static fn (mixed $value): float => (float) $value, array_values($values));

        if ($series === []) {
            return ['method' => $method, 'anomalies' => []];
        }

        return match ($method) {
            'iqr' => $this->byIqr($series),
            'zscore' => $this->byZScore($series, $threshold),
            default => throw new \InvalidArgumentException(sprintf(
                'Unknown anomaly method [%s]; use zscore or iqr.',
                $method,
            )),
        };
    }

    /**
     * Flags points beyond a number of standard deviations.
     *
     * @param list<float> $series Numeric values.
     * @param float $threshold Absolute z-score above which a point is an anomaly.
     * @return array<string, mixed> Method statistics and the anomalies found.
     */
    private function byZScore(array $series, float $threshold): array
    {
        $count = count($series);
        $mean = $this->sum($series)->parts($count);

        $variance = number(0.0);
        foreach ($series as $value) {
            $variance = $variance->add(number($value)->subtract($mean)->raise(2));
        }

        $deviation = $variance->parts($count)->raise(0.5);
        $limit = number($threshold);
        $anomalies = [];

        foreach ($series as $index => $value) {
            $deviationFromMean = number($value)->subtract($mean);

            if ($deviation->equals(0.0)) {
                continue;
            }

            $score = $deviationFromMean->parts($deviation);
            $magnitude = $score->lt(0) ? $score->negate() : $score;

            if ($magnitude->gt($limit)) {
                $anomalies[] = [
                    'index' => $index,
                    'value' => $value,
                    'z_score' => $score->round(4)->yield(),
                ];
            }
        }

        return [
            'method' => 'zscore',
            'mean' => $mean->round(4)->yield(),
            'stddev' => $deviation->round(4)->yield(),
            'threshold' => $limit->yield(),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Flags points outside the interquartile fences.
     *
     * @param list<float> $series Numeric values.
     * @return array<string, mixed> Method statistics and the anomalies found.
     */
    private function byIqr(array $series): array
    {
        sort($series);

        $first = $this->percentile($series, 0.25);
        $third = $this->percentile($series, 0.75);
        $spread = $third->subtract($first);
        $fence = number(self::IQR_FACTOR)->times($spread);

        $lower = $first->subtract($fence);
        $upper = $third->add($fence);
        $anomalies = [];

        foreach ($series as $index => $value) {
            $point = number($value);

            if ($point->lt($lower) || $point->gt($upper)) {
                $anomalies[] = [
                    'index' => $index,
                    'value' => $value,
                ];
            }
        }

        return [
            'method' => 'iqr',
            'lower' => $lower->yield(),
            'upper' => $upper->yield(),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Interpolates a percentile of a sorted series.
     *
     * The position between the two neighbouring samples is linear, so a
     * quartile of an even-length series lands between two values instead of
     * collapsing onto one of them.
     *
     * @param list<float> $series Sorted numeric values.
     * @param float $percentile Fraction between 0 and 1.
     * @return \Strukt\Number Interpolated percentile.
     */
    private function percentile(array $series, float $percentile): Number
    {
        $count = count($series);
        $position = number($count - 1)->times($percentile);

        $lowerIndex = (int) floor($position->yield());
        $upperIndex = min((int) ceil($position->yield()), $count - 1);

        $lower = number($series[$lowerIndex]);
        if ($lowerIndex === $upperIndex) {
            return $lower;
        }

        $distance = $position->subtract($lowerIndex);

        return $lower->add(number($series[$upperIndex])->subtract($lower)->times($distance));
    }

    /**
     * Sums a list of numbers.
     *
     * @param list<float> $series Numeric values.
     * @return \Strukt\Number Total.
     */
    private function sum(array $series): Number
    {
        $total = number(0.0);

        foreach ($series as $value) {
            $total = $total->add($value);
        }

        return $total;
    }
}
