<?php
declare (strict_types = 1);
namespace Strukt\Owl\Nlp;

final class Readability
{
    public function analyze(string $text): array
    {
        $w     = Text::words($text);
        $s     = Text::sentences($text);
        $wc    = count($w);
        $sc    = max(1, count($s));
        $sy    = array_sum(array_map([$this, 'syllables'], $w));
        $ease  = $wc ? 206.835 - 1.015 * ($wc / $sc) - 84.6 * ($sy / $wc) : 0;
        $grade = $wc ? .39 * ($wc / $sc) + 11.8 * ($sy / $wc) - 15.59 : 0;
        return [
            'words'                => $wc,
            'sentences'            => count($s),
            'syllables'            => $sy,
            'reading_time_minutes' => round($wc / 200, 2),
            'flesch_reading_ease'  => round($ease, 2),
            'flesch_kincaid_grade' => round($grade, 2),
        ];
    }

    private function syllables(string $w): int
    {
        $w = preg_replace('/[^a-z]/i', '', mb_strtolower($w)) ?? '';
        if ($w === '' || strlen($w) <= 3) {
            return $w === '' ? 0 : 1;
        }

        $w = preg_replace('/(?:[^aeiouy]e)$/', '', $w) ?? $w;
        preg_match_all('/[aeiouy]+/', $w, $m);

        return max(1, count($m[0]));
    }
}
