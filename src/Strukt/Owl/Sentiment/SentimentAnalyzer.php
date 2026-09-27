<?php
declare (strict_types = 1);
namespace Strukt\Owl\Sentiment;

final class SentimentAnalyzer
{
    public function __construct(private readonly array $lexicon = [])
    {}
    public function analyze(string $text): array
    {
        $lex = $this->lexicon ?: $this->default();
        preg_match_all('/[\p{L}\p{N}]+|[!?]+/u', $text, $m);
        $tokens = $m[0] ?? [];
        $scores = [];
        $raw    = 0.0;

        foreach ($tokens as $i => $token) {

            $w = mb_strtolower($token);
            if (! isset($lex[$w])) {
                continue;
            }

            $s = (float) $lex[$w];
            foreach (array_slice($tokens, max(0, $i - 3), 3) as $p) {

                $p = mb_strtolower($p);
                if (in_array($p, ['not', "don't", 'never', 'no'], true)) {
                    $s *= -0.74;
                }
                if (in_array($p, ['very', 'really', 'extremely', 'absolutely'], true)) {
                    $s *= 1.25;
                }
            }

            $raw      += $s;
            $scores[] = $s;
        }

        $raw      += ($raw > 0 ? 1 : -1) * min(.5, substr_count($text, '!') * .05);
        $pos      = array_sum(array_map(fn($x) => max(0, $x), $scores));
        $neg      = abs(array_sum(array_map(fn($x) => min(0, $x), $scores)));
        $total    = $pos + $neg;
        $compound = $raw / sqrt($raw * $raw + 15);
        return [
            'positive' => $total ? round($pos / $total, 4) : 0.0,
            'negative' => $total ? round($neg / $total, 4) : 0.0,
            'neutral'  => $total ? 0.0 : 1.0,
            'compound' => round($compound, 4),
            'label'    => $compound >= .05 ? 'positive' : ($compound <= -.05 ? 'negative' : 'neutral'),
        ];
    }

    private function default(): array
    {
        return ['amazing' => 3.2,
            'awesome'         => 3.1,
            'excellent'       => 3,
            'fantastic'       => 3.2,
            'wonderful'       => 2.8,
            'great'           => 2.5,
            'good'            => 2,
            'love'            => 3,
            'loved'           => 3,
            'happy'           => 2.5,
            'joy'             => 2.6,
            'win'             => 2.4,
            'winner'          => 2.6,
            'success'         => 2.5,
            'successful'      => 2.5,
            'best'            => 3,
            'nice'            => 1.8,
            'helpful'         => 1.8,
            'beautiful'       => 2.3,
            'perfect'         => 3,
            'brilliant'       => 2.8,
            'bad'             => -2.2,
            'terrible'        => -3,
            'awful'           => -3,
            'horrible'        => -3,
            'hate'            => -3,
            'hated'           => -3,
            'sad'             => -2.2,
            'angry'           => -2.5,
            'anger'           => -2.4,
            'fail'            => -2.4,
            'failed'          => -2.5,
            'failure'         => -2.5,
            'worst'           => -3,
            'poor'            => -2,
            'problem'         => -1.8,
            'broken'          => -2.4,
            'wrong'           => -2,
            'disappointing'   => -2.2,
            'disappointed'    => -2.4,
            'fear'            => -2,
            'scared'          => -2.2,
            'fraud'           => -3,
            'scam'            => -3];
    }
}
