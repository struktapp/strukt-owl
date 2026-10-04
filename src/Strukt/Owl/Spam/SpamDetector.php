<?php

declare(strict_types=1);

namespace Strukt\Owl\Spam;

/**
 * Flags likely spam from a small weighted heuristic.
 *
 * The score accumulates marker hits, exclamation density, and link presence,
 * then saturates at 1 so a long message cannot exceed the threshold by
 * piling up markers.
 */
final class SpamDetector
{
    /**
     * Marker phrases and their score contribution.
     *
     * @var array<string, float> Phrase to weight, where weight indicates strength of a promotional signal.
     */
    private const MARKERS = [
        'winner' => 0.12,
        'won' => 0.12,
        'prize' => 0.12,
        'free' => 0.12,
        'claim' => 0.12,
        'urgent' => 0.12,
        'congratulations' => 0.12,
        'cash' => 0.12,
        'money' => 0.12,
        'offer' => 0.12,
        'click' => 0.12,
        'bonus' => 0.12,
        'guaranteed' => 0.12,
        'limited time' => 0.12,
    ];

    /** Score added per exclamation mark. */
    private const EXCLAMATION_WEIGHT = 0.04;

    /** Score added when a URL is present. */
    private const LINK_WEIGHT = 0.15;

    /** Score at or above which a message is spam. */
    private const THRESHOLD = 0.35;

    /** Highest score the heuristic reports. */
    private const CEILING = 1;

    /**
     * @param array<string, float> $markers Replacement marker phrases with weights.
     */
    public function __construct(private readonly array $markers = [])
    {
    }

    /**
     * @return array<string, float> Marker phrases to flag.
     */
    private function markers(): array
    {
        return $this->markers !== [] ? $this->markers : self::MARKERS;
    }

/**
 * Scores text for spam likelihood.
 *
 * The score accumulates marker hits, exclamation density, and link presence
 * through a pipeline of weighted stages, then saturates at the ceiling so a
 * long message cannot blow past the threshold by piling up markers.
 *
 * @param string $text Raw input.
 * @return array{label: string, confidence: float, score: float, matches: list<string>} Spam assessment.
 */
public function analyze(string $text): array
{
    $haystack = mb_strtolower($text);
    $markers = $this->markers();
    $matches = array_values(array_filter(
        array_keys($markers),
        static fn (string $marker): bool => str_contains($haystack, $marker),
    ));

    $base = (float) array_sum(array_intersect_key($markers, array_flip($matches)));

    $score = pipeline([$base])
        ->next(fn (float $base): float => $base + substr_count($haystack, '!') * self::EXCLAMATION_WEIGHT)
        ->next(fn (float $base): float => $base + ($this->hasLink($haystack) ? self::LINK_WEIGHT : 0.0))
        ->next(static fn (float $total): float => min(self::CEILING, $total))
        ->yield();

    $score = number($score);
    $spam = $score->gte(self::THRESHOLD);
    $confidence = $spam ? $score : number(1)->subtract($score);

    return [
        'label' => $spam ? 'spam' : 'normal',
        'confidence' => $confidence->round(4)->yield(),
        'score' => $score->round(4)->yield(),
        'matches' => $matches,
    ];
}

    /**
     * Checks whether the message contains an http or https link.
     *
     * @param string $haystack Lowercased message.
     * @return bool True when a link is present.
     */
    private function hasLink(string $haystack): bool
    {
        return preg_match('/https?:\/\//', $haystack) === 1;
    }
}
