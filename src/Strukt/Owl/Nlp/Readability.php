<?php

declare(strict_types=1);

namespace Strukt\Owl\Nlp;

/**
 * Readability metrics for English prose.
 *
 * The Flesch formulas combine average sentence length with average syllables
 * per word, so both ratios are computed once and reused across the two
 * scores.
 */
final class Readability
{
    /** Flesch constant. */
    private const EASE_BASE = 206.835;

    /** Flesch sentence-length weight. */
    private const EASE_SENTENCE_WEIGHT = 1.015;

    /** Flesch syllable weight. */
    private const EASE_SYLLABLE_WEIGHT = 84.6;

    /** Flesch-Kincaid sentence-length weight. */
    private const GRADE_SENTENCE_WEIGHT = 0.39;

    /** Flesch-Kincaid syllable weight. */
    private const GRADE_SYLLABLE_WEIGHT = 11.8;

    /** Flesch-Kincaid constant. */
    private const GRADE_BASE = 15.59;

    /** Assumed reading speed in words per minute. */
    private const WORDS_PER_MINUTE = 200;

    /**
     * Scores readability.
     *
     * Empty input reports zero for every derived metric rather than dividing
     * by zero.
     *
     * @param string $text Raw input.
     * @return array{words: int, sentences: int, syllables: int, reading_time_minutes: float, flesch_reading_ease: float, flesch_kincaid_grade: float} Metrics.
     */
    public function analyze(string $text): array
    {
        $words = Text::words($text);
        $sentences = Text::sentences($text);
        $syllables = array_sum(array_map($this->countSyllables(...), $words));

        $wordCount = count($words);
        $sentenceCount = count($sentences);
        $readingTime = number($wordCount)->parts(self::WORDS_PER_MINUTE)->round(2);

        if ($wordCount === 0) {
            return [
                'words' => $wordCount,
                'sentences' => $sentenceCount,
                'syllables' => $syllables,
                'reading_time_minutes' => $readingTime->yield(),
                'flesch_reading_ease' => 0.0,
                'flesch_kincaid_grade' => 0.0,
            ];
        }

        $perSentence = number($wordCount)->parts(max(1, $sentenceCount));
        $perWord = number($syllables)->parts($wordCount);

        $ease = number(self::EASE_BASE)
            ->subtract(number(self::EASE_SENTENCE_WEIGHT)->times($perSentence))
            ->subtract(number(self::EASE_SYLLABLE_WEIGHT)->times($perWord));

        $grade = number(self::GRADE_SENTENCE_WEIGHT)
            ->times($perSentence)
            ->add(number(self::GRADE_SYLLABLE_WEIGHT)->times($perWord))
            ->subtract(self::GRADE_BASE);

        return [
            'words' => $wordCount,
            'sentences' => $sentenceCount,
            'syllables' => $syllables,
            'reading_time_minutes' => $readingTime->yield(),
            'flesch_reading_ease' => $ease->round(2)->yield(),
            'flesch_kincaid_grade' => $grade->round(2)->yield(),
        ];
    }

/**
 * Estimates syllables in a single word.
 *
 * Vowel groups are counted after dropping a silent trailing "e"; short
 * words always report at least one syllable. The cleaning stages are a monad
 * chain that strips accents and non-letters step by step.
 *
 * @param string $word Word to measure.
 * @return int Estimated syllable count.
 */
private function countSyllables(string $word): int
{
    $word = monad($word)
        ->map(static fn (string $word): string => mb_strtolower($word))
        ->map(static fn (string $word): string => preg_replace('/[^a-z]/i', '', $word) ?? '')
        ->join();

    if ($word === '') {
        return 0;
    }

    if (strlen($word) <= 3) {
        return 1;
    }

    $word = preg_replace('/(?:[^aeiouy]e)$/', '', $word) ?? $word;
    preg_match_all('/[aeiouy]+/', $word, $matches);

    return max(1, count($matches[0]));
}
}
