<?php

declare(strict_types=1);

namespace Strukt\Owl\Nlp;

/**
 * Produces an extractive summary by ranking sentences against word frequency.
 *
 * Sentences are scored by the mean of their log term frequencies, then the
 * top scoring ones are returned in their original document order so the
 * summary still reads as prose.
 */
final class Summarizer
{
    /**
     * Summarizes text down to the given number of sentences.
     *
     * @param string $text Raw input.
     * @param int $sentences Maximum number of sentences to keep.
     * @return string Summary text.
     */
    public function summarize(string $text, int $sentences = 3): string
    {
        $units = Text::sentences($text);

        if ($sentences <= 0) {
            return '';
        }

        if (count($units) <= $sentences) {
            return implode(' ', $units);
        }

        $frequencies = Text::termFrequencies(Text::words($text, true));
        $scores = [];

        foreach ($units as $index => $sentence) {
            $total = number(0.0);

            foreach (Text::words($sentence, true) as $word) {
                $total = $total->add(log(1 + ($frequencies[$word] ?? 0)));
            }

            $length = max(1, count(Text::words($sentence)));
            $scores[$index] = $total->parts($length)->yield();
        }

        arsort($scores);

        $chosen = array_slice(array_keys($scores), 0, $sentences);
        sort($chosen);

        return implode(' ', array_map(static fn (int $index): string => $units[$index], $chosen));
    }
}
