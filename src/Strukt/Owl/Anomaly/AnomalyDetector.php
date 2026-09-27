<?php
declare (strict_types = 1);
namespace Strukt\Owl\Anomaly;

final class AnomalyDetector
{
    public function detect(array $values, string $method = 'zscore', float $threshold = 3.0): array
    {
        $v = array_map('floatval', $values);
        if (! $v) {
            return [
                'method'    => $method,
                'anomalies' => [],
            ];
        }

        if ($method === 'iqr') {

            sort($v);
            $q1  = $this->percentile($v, .25);
            $q3  = $this->percentile($v, .75);
            $iqr = $q3 - $q1;
            $lo  = $q1 - 1.5 * $iqr;
            $hi  = $q3 + 1.5 * $iqr;
            $a   = [];

            foreach ($v as $i => $x) {
                if ($x < $lo || $x > $hi) {
                    $a[] = ['index' => $i, 'value' => $x];
                }
            }

            return [
                'method'    => 'iqr',
                'lower'     => $lo,
                'upper'     => $hi,
                'anomalies' => $a];
        }

        $mean = array_sum($v) / count($v);
        $sd   = sqrt(array_sum(array_map(fn($x) => ($x - $mean) ** 2, $v)) / count($v));
        $a    = [];

        foreach ($v as $i => $x) {
            $z = $sd ? ($x - $mean) / $sd : 0;
            if (abs($z) > $threshold) {
                $a[] = ['index' => $i, 'value' => $x, 'z_score' => round($z, 4)];
            }
        }

        return ['method' => 'zscore',
            'mean'           => round($mean, 4),
            'stddev'         => round($sd, 4),
            'threshold'      => $threshold,
            'anomalies'      => $a];
    }

    private function percentile(array $a, float $p): float
    {
        $n  = count($a);
        $i  = ($n - 1) * $p;
        $lo = floor($i);
        $hi = ceil($i);

        return $a[$lo] + ($a[$hi] ?? $a[$lo]) - $a[$lo] * ($hi - $i) - $a[$lo] * ($i - $lo);
    }
}
