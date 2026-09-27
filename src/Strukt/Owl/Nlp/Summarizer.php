<?php
declare (strict_types = 1);
namespace Strukt\Owl\Nlp;

final class Summarizer
{
    public function summarize(string $text, int $sentences = 3): string
    {
        $s = Text::sentences($text);
        if (count($s) <= $sentences) {
            return implode(' ', $s);
        }

        $freq = [];
        foreach (Text::words($text, true) as $w) {
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }

        $scores = [];
        foreach ($s as $i => $sentence) {
            $score = 0;
            foreach (Text::words($sentence, true) as $w) {
                $score += log(1 + $freq[$w]);
            }

            $scores[$i] = $score / max(1, count(Text::words($sentence)));
        }

        arsort($scores);
        $chosen  = array_slice(array_keys($scores), 0, $sentences);
        sort($chosen);

        return implode(' ', array_map(fn($i) => $s[$i], $chosen));
    }
}
