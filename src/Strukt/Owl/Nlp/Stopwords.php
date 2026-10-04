<?php

declare(strict_types=1);

namespace Strukt\Owl\Nlp;

/**
 * Common stop words used when a caller does not supply its own list.
 *
 * The default list mixes English function words with a small Swahili set,
 * which is enough to keep keyword and summary scores from being dominated by
 * filler in the two languages Owl is used with most.
 */
final class Stopwords
{
    /**
     * @var list<string> Words removed during tokenization.
     */
    private const WORDS = [
        // English
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'been', 'but', 'by', 'for',
        'from', 'has', 'have', 'he', 'her', 'his', 'i', 'if', 'in', 'is', 'it',
        'its', 'me', 'my', 'of', 'on', 'or', 'our', 'she', 'that', 'the',
        'their', 'them', 'there', 'they', 'this', 'to', 'was', 'we', 'were',
        'what', 'when', 'where', 'which', 'who', 'will', 'with', 'you', 'your',
        // Swahili
        'na', 'ni', 'ya', 'wa', 'kwa', 'katika', 'hii', 'hiyo', 'kama', 'au',
        'mimi', 'sisi', 'yeye',
    ];

    /**
     * Memoized lookup table, keyed by word.
     *
     * @var array<string, true>|null
     */
    private ?array $lookup = null;

    /**
     * @param list<string> $words Replacement stop words.
     */
    public function __construct(private readonly array $words = [])
    {
    }

    /**
     * @return list<string> Stop words in declaration order.
     */
    public function all(): array
    {
        return $this->words();
    }

    /**
     * Checks whether a word is a stop word.
     *
     * @param string $word Word to test, already normalized to lowercase.
     * @return bool True when the word should be dropped.
     */
    public function contains(string $word): bool
    {
        return isset($this->lookup()[$word]);
    }

    /**
     * @return list<string> Stop words to match against.
     */
    private function words(): array
    {
        return $this->words !== [] ? $this->words : self::WORDS;
    }

    /**
     * Builds the lookup table once per instance.
     *
     * @return array<string, true> Set of stop words.
     */
    private function lookup(): array
    {
        return $this->lookup ??= array_fill_keys($this->words(), true);
    }
}