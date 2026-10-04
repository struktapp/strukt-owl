<?php

declare(strict_types=1);

namespace Strukt\Owl\Nlp;

/**
 * Text normalization and tokenization shared by the analyzers.
 *
 * Every analyzer in Owl starts here, so normalization rules live in one place
 * and stay consistent between keyword extraction, classification, and
 * similarity scoring.
 */
final class Text
{
/**
 * Lowercases text and strips punctuation.
 *
 * Letters, digits, whitespace, apostrophes, and hyphens survive, so
 * "Kenya's" and "e-commerce" stay single tokens. The three cleaning stages
 * are a monad chain from the math primitives: each maps the string to its
 * next form without mutating.
 *
 * @param string $text Raw input.
 * @return string Normalized text.
 */
public static function normalize(string $text): string
{
    return monad($text)
        ->map(static fn (string $text): string => mb_strtolower(trim($text)))
        ->map(static fn (string $text): string => preg_replace('/[^\p{L}\p{N}\s\'\-]+/u', ' ', $text) ?? $text)
        ->map(static fn (string $text): string => preg_replace('/\s+/u', ' ', $text) ?? $text)
        ->join();
}

/**
 * Splits text into word tokens.
 *
 * A positional pipeline sequences normalize, tokenize, and the optional stop
 * word drop: each step pipes its result into the next.
 *
 * @param string $text Raw input.
 * @param bool $removeStop Whether to drop stop words.
 * @param Stopwords $stopwords Stop list to honor when dropping.
 * @return list<string> Word tokens in document order.
 */
public static function words(string $text, bool $removeStop = false, Stopwords $stopwords = new Stopwords()): array
{
    return pipeline([self::normalize($text)])
        ->next(static function (string $normalized): array {
            preg_match_all('/[\p{L}\p{N}]+(?:[\'\-][\p{L}\p{N}]+)*/u', $normalized, $matches);

            return $matches[0] ?? [];
        })
        ->next(fn (array $words): array => $removeStop
            ? array_values(array_filter(
                $words,
                fn (string $word): bool => !$stopwords->contains($word),
            ))
            : $words)
        ->yield();
}

/**
 * Splits text into sentence boundaries.
 *
 * A positional pipeline splits on punctuation, then drops fragments without
 * any word token so trailing punctuation does not produce empty sentences.
 *
 * @param string $text Raw input.
 * @return list<string> Sentences in document order.
 */
public static function sentences(string $text): array
{
    return pipeline([trim($text)])
        ->next(static fn (string $text): array => preg_split(
            '/(?<=[.!?])\s+/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [])
        ->next(static fn (array $parts): array => array_values(array_filter(
            $parts,
            static fn (string $sentence): bool => self::words($sentence) !== [],
        )))
        ->yield();
}

    /**
     * Counts term frequencies for a token list.
     *
     * @param list<string> $words Word tokens.
     * @return array<string, int> Counts keyed by term.
     */
    public static function termFrequencies(array $words): array
    {
        $counts = [];
        foreach ($words as $word) {
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Blocks instantiation of the utility class.
     *
     * @return void
     */
    private function __construct()
    {
    }
}
