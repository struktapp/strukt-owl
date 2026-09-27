<?php
declare (strict_types = 1);
namespace Strukt\Owl\Classifier;

use Strukt\Owl\Nlp\Text;

final class NaiveBayesClassifier
{
    private array $docs = [], $counts = [], $totals = [], $vocab = [];
    public function train(string $class, array $documents): void
    {
        foreach ($documents as $d) {
            $this->docs[$class] = ($this->docs[$class] ?? 0) + 1;
            foreach (Text::words((string) $d, true) as $w) {
                $this->counts[$class][$w] = ($this->counts[$class][$w] ?? 0) + 1;
                $this->totals[$class]     = ($this->totals[$class] ?? 0) + 1;
                $this->vocab[$w]          = 1;
            }
        }
    }

    public function predict(string $text): array
    {
        if (! $this->docs) {
            throw new \LogicException('Classifier has not been trained.');
        }

        $tokens = Text::words($text, true);
        $total  = array_sum($this->docs);
        $vsize  = max(1, count($this->vocab));
        $scores = [];
        foreach ($this->docs as $c => $n) {
            $score = log($n / $total);
            $tw    = $this->totals[$c] ?? 0;
            foreach ($tokens as $w) {
                $score += log((($this->counts[$c][$w] ?? 0) + 1) / ($tw + $vsize));
            }

            $scores[$c] = $score;
        }$max = max($scores);
        $p    = [];
        foreach ($scores as $c => $s) {
            $p[$c] = exp($s - $max);
        }

        $sum = array_sum($p);
        foreach ($p as $c => $v) {
            $p[$c] = $v / $sum;
        }

        arsort($p);
        $best = array_key_first($p);
        return [
            'class'      => $best,
            'confidence' => round($p[$best], 4),
            'scores'     => array_map(fn($v) => round($v, 4), $p),
        ];
    }

    public function export(): array
    {
        return [
            'docs'   => $this->docs,
            'counts' => $this->counts,
            'totals' => $this->totals,
            'vocab'  => array_keys($this->vocab)];
    }

    public static function import(array $data): self
    {
        $x         = new self();
        $x->docs   = $data['docs'] ?? [];
        $x->counts = $data['counts'] ?? [];
        $x->totals = $data['totals'] ?? [];
        foreach ($data['vocab'] ?? [] as $w) {
            $x->vocab[$w] = 1;
        }

        return $x;
    }
}
