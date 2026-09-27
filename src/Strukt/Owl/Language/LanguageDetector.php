<?php
declare (strict_types = 1);
namespace Strukt\Owl\Language;

final class LanguageDetector
{
    private array $profiles = ['en' => ['the' => 8, 'and' => 7, 'ing' => 6, 'ion' => 5, 'this' => 4, 'that' => 4, 'with' => 4, 'for' => 4], 'fr' => ['les' => 8, 'des' => 7, 'que' => 7, 'ent' => 6, 'une' => 5, 'dans' => 5, 'pour' => 5, 'avec' => 4], 'de' => ['der' => 8, 'die' => 8, 'und' => 7, 'den' => 6, 'das' => 6, 'ein' => 5, 'ist' => 5, 'mit' => 4], 'es' => ['que' => 8, 'los' => 7, 'las' => 7, 'del' => 6, 'una' => 6, 'para' => 5, 'con' => 5, 'por' => 4], 'it' => ['che' => 8, 'del' => 7, 'della' => 6, 'gli' => 6, 'una' => 5, 'per' => 5, 'con' => 5, 'non' => 5], 'pt' => ['que' => 8, 'dos' => 7, 'das' => 7, 'uma' => 6, 'para' => 5, 'com' => 5, 'não' => 5, 'ção' => 5], 'sw' => ['ya' => 8, 'wa' => 8, 'na' => 7, 'katika' => 7, 'kwa' => 7, 'hii' => 6, 'hiyo' => 6, 'ni' => 5, 'habari' => 5, 'uko' => 5]];

    public function detect(string $text): array
    {
        $text   = mb_strtolower($text);
        $scores = [];
        foreach ($this->profiles as $l => $f) {
            $scores[$l] = 0;
            foreach ($f as $term => $weight) {
                $scores[$l] += substr_count($text, $term) * $weight;
            }
        }

        arsort($scores);
        $best   = array_key_first($scores);
        $total  = array_sum($scores);
        return [
            'language'   => $best,
            'confidence' => $total ? round($scores[$best] / $total, 4) : 0,
            'scores'     => $scores,
        ];
    }
}
