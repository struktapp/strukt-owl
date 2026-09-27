<?php
declare (strict_types = 1);
namespace Strukt\Owl\Spam;

final class SpamDetector
{
    private array $spam = ['winner',
        'won',
        'prize',
        'free',
        'claim',
        'urgent',
        'congratulations',
        'cash',
        'money',
        'offer',
        'click',
        'bonus',
        'guaranteed',
        'limited time',
    ];

    public function analyze(string $text): array
    {
        $t    = mb_strtolower($text);
        $hits = [];
        foreach ($this->spam as $w) {
            if (str_contains($t, $w)) {
                $hits[] = $w;
            }
        }

        $score = min(1, count($hits) * .12 + substr_count($t, '!') * .04 + ((preg_match('/https?:\/\//', $t)) ? .15 : 0));

        return ['label' => $score >= .35 ? 'spam' : 'normal',
            'confidence'    => round($score >= .35 ? $score : 1 - $score, 4),
            'score'         => round($score, 4),
            'matches'       => $hits];
    }
}
