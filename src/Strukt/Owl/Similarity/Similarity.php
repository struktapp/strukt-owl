<?php
declare (strict_types = 1);
namespace Strukt\Owl\Similarity;

use Strukt\Owl\Nlp\Text;

final class Similarity
{
    public function cosine(string $a, string $b): float
    {
        $a     = $this->v(Text::words($a, true));
        $b     = $this->v(Text::words($b, true));
        $terms = array_unique(array_merge(array_keys($a), array_keys($b)));
        $dot   = $na   = $nb   = 0;

        foreach ($terms as $t) {

            $x    = $a[$t] ?? 0;
            $y    = $b[$t] ?? 0;
            $dot += $x * $y;
            $na  += $x * $x;
            $nb  += $y * $y;
        }
        return $na && $nb ? $dot / sqrt($na * $nb) : 0;
    }

    public function jaccard(string $a, string $b): float
    {
        $a = array_unique(Text::words($a, true));
        $b = array_unique(Text::words($b, true));
        $u = count(array_unique(array_merge($a, $b)));

        return $u ? count(array_intersect($a, $b)) / $u : 1;
    }

    public function dice(string $a, string $b): float
    {
        $a = array_unique(Text::words($a, true));
        $b = array_unique(Text::words($b, true));
        $n = count($a) + count($b);

        return $n ? 2 * count(array_intersect($a, $b)) / $n : 1;
    }

    public function levenshtein(string $a, string $b): int
    {
        return levenshtein(mb_strtolower($a), mb_strtolower($b));
    }

    public function jaroWinkler(string $a, string $b): float
    {
        $a = mb_strtolower($a);
        $b = mb_strtolower($b);

        if ($a === $b) {
            return 1;
        }

        if ($a === '' || $b === '') {
            return 0;
        }

        $la = mb_strlen($a);
        $lb = mb_strlen($b);
        $r  = max((int) floor(max($la, $lb) / 2) - 1, 0);
        $ma = array_fill(0, $la, false);
        $mb = array_fill(0, $lb, false);
        $m  = 0;
        for ($i = 0; $i < $la; $i++) {
            for ($j = max(0, $i - $r); $j <= min($i + $r, $lb - 1); $j++) {
                if (! $mb[$j] && mb_substr($a, $i, 1) === mb_substr($b, $j, 1)) {
                    $ma[$i] = true;
                    $mb[$j] = true;
                    $m++;
                    break;
                }
            }
        }

        if (! $m) {
            return 0;
        }

        $ta = $tb = [];
        for ($i = 0; $i < $la; $i++) {
            if ($ma[$i]) {
                $ta[] = mb_substr($a, $i, 1);
            }
        }

        for ($i = 0; $i < $lb; $i++) {
            if ($mb[$i]) {
                $tb[] = mb_substr($b, $i, 1);
            }
        }

        $tr = 0;
        foreach ($ta as $i => $c) {
            if ($c !== $tb[$i]) {
                $tr++;
            }
        }

        $tr /= 2;
        $j  = (($m / $la) + ($m / $lb) + (($m - $tr) / $m)) / 3;
        $p  = 0;
        while ($p < min(4, $la, $lb) && mb_substr($a, $p, 1) === mb_substr($b, $p, 1)) {
            $p++;
        }
        return $j + $p * .1 * (1 - $j);
    }

    private function v(array $w): array
    {
        $v = [];
        foreach ($w as $x) {
            $v[$x] = ($v[$x] ?? 0) + 1;
        }

        return $v;
    }
}
