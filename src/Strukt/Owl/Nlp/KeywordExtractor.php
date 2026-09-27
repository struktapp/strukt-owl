<?php
declare (strict_types = 1);
namespace Strukt\Owl\Nlp;

final class KeywordExtractor
{
    public function extract(string $text, int $limit = 10): array
    {
        $docs = Text::sentences($text);
        $tf   = [];
        $df   = [];

        foreach ($docs as $d) {
            $seen = [];
            foreach (Text::words($d, true) as $w) {
                if (mb_strlen($w) < 3) {
                    continue;
                }

                $tf[$w] = ($tf[$w] ?? 0) + 1;
                if (! isset($seen[$w])) {
                    $df[$w]   = ($df[$w] ?? 0) + 1;
                    $seen[$w] = 1;
                }
            }
        }

        $n = max(count($docs), 1);
        $s = [];
        foreach ($tf as $w => $f) {
            $s[$w] = $f * (log(($n + 1) / (($df[$w] ?? 1) + 1)) + 1);
        }

        arsort($s);
        return array_slice(array_keys($s), 0, $limit);
    }
}
