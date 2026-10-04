<?php

declare(strict_types=1);

namespace Strukt\Owl\Nlp;

/**
 * Extracts ranked keywords with a TF-IDF style score.
 *
 * Terms are scored per sentence so a word repeated throughout the document
 * is dampened by its document frequency.
 */
final class KeywordExtractor
{
    /**
     * Shortest token considered a keyword.
     */
    private const MIN_LENGTH = 3;

    /**
     * Extracts the highest scoring keywords.
     *
     * @param string $text Raw input.
     * @param int $limit Maximum number of keywords to return.
     * @return list<string> Keywords ordered by descending score.
     */
    public function extract(string $text, int $limit = 10): array
    {
        $sentences = Text::sentences($text);
        if ($sentences === [] || $limit <= 0) {
            return [];
        }

        $frequencies = [];
        $documents = [];

        foreach ($sentences as $sentence) {
            $seen = [];
            foreach (Text::words($sentence, true) as $word) {
                if (mb_strlen($word) < self::MIN_LENGTH) {
                    continue;
                }

                $frequencies[$word] = ($frequencies[$word] ?? 0) + 1;

                if (!isset($seen[$word])) {
                    $documents[$word] = ($documents[$word] ?? 0) + 1;
                    $seen[$word] = true;
                }
            }
        }

        $corpus = number(max(count($sentences), 1));
        $scores = [];

        foreach ($frequencies as $word => $frequency) {
            $inverse = log($corpus->add(1)->parts(number($documents[$word])->add(1))->yield());
            $scores[$word] = number($frequency)->times(number($inverse)->add(1))->yield();
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, $limit);
    }
}
