<?php

declare(strict_types=1);

namespace Strukt\Owl\Emotion;

use Strukt\Owl\Nlp\Text;

/**
 * Scores text against labelled emotion word groups.
 *
 * Each emotion counts its own lexicon matches, and the counts are converted
 * into shares with the math primitives so every emotion is reported on the
 * same scale.
 */
final class EmotionAnalyzer
{
    /**
     * Emotion word groups.
     *
     * @var array<string, list<string>> Words grouped by emotion.
     */
    private const LEXICON = [
        'joy' => ['happy', 'joy', 'love', 'loved', 'excellent', 'wonderful', 'delighted', 'celebrate', 'win', 'winner', 'success', 'fun'],
        'anger' => ['angry', 'anger', 'furious', 'hate', 'hated', 'rage', 'annoyed', 'outraged'],
        'sadness' => ['sad', 'sadness', 'cry', 'crying', 'grief', 'lonely', 'loss', 'hurt', 'disappointed'],
        'fear' => ['fear', 'afraid', 'scared', 'terrified', 'danger', 'threat', 'panic', 'worried'],
        'surprise' => ['surprise', 'surprised', 'amazing', 'unexpected', 'suddenly', 'wow'],
        'disgust' => ['disgusting', 'disgust', 'awful', 'horrible', 'nasty', 'gross'],
    ];

    /**
     * @param array<string, list<string>> $lexicon Replacement emotion word groups.
     */
    public function __construct(private readonly array $lexicon = [])
    {
    }

    /**
     * @return array<string, list<string>> Emotion word groups to score against.
     */
    private function lexicon(): array
    {
        return $this->lexicon !== [] ? $this->lexicon : self::LEXICON;
    }

    /**
     * Scores text for emotion.
     *
     * @param string $text Raw input.
     * @return array{emotions: array<string, float>, dominant: string, matches: int} Emotion shares ordered by descending share.
     */
    public function analyze(string $text): array
    {
        $counts = array_fill_keys(array_keys($this->lexicon()), 0);

        foreach (Text::words($text, true) as $word) {
            foreach ($this->lexicon() as $emotion => $words) {
                if (in_array($word, $words, true)) {
                    $counts[$emotion]++;
                }
            }
        }

        $total = number(array_sum($counts));
        $emotions = [];

        foreach ($counts as $emotion => $count) {
            $emotions[$emotion] = $total->gt(0)
                ? number($count)->parts($total)->round(4)->yield()
                : 0.0;
        }

        arsort($emotions);

        return [
            'emotions' => $emotions,
            'dominant' => (string) array_key_first($emotions),
            'matches' => array_sum($counts),
        ];
    }
}
