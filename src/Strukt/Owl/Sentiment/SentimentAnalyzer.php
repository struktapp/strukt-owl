<?php

declare(strict_types=1);

namespace Strukt\Owl\Sentiment;

use Strukt\Number;

/**
 * Scores text against a valence lexicon.
 *
 * Scores are accumulated through the Strukt math primitives, so every
 * weighting step is a typed value object rather than a loose float. The
 * compound score uses the same normalized range as the VADER family of
 * lexicons, which keeps the result comparable across inputs.
 */
final class SentimentAnalyzer
{
    /**
     * Default valence lexicon.
     *
     * @var array<string, float> Word to valence score.
     */
    private const DEFAULT_LEXICON = [
        // Positive
        'amazing' => 3.2,
        'awesome' => 3.1,
        'excellent' => 3.0,
        'fantastic' => 3.2,
        'wonderful' => 2.8,
        'great' => 2.5,
        'good' => 2.0,
        'love' => 3.0,
        'loved' => 3.0,
        'happy' => 2.5,
        'joy' => 2.6,
        'win' => 2.4,
        'winner' => 2.6,
        'success' => 2.5,
        'successful' => 2.5,
        'best' => 3.0,
        'nice' => 1.8,
        'helpful' => 1.8,
        'beautiful' => 2.3,
        'perfect' => 3.0,
        'brilliant' => 2.8,
        // Negative
        'bad' => -2.2,
        'terrible' => -3.0,
        'awful' => -3.0,
        'horrible' => -3.0,
        'hate' => -3.0,
        'hated' => -3.0,
        'sad' => -2.2,
        'angry' => -2.5,
        'anger' => -2.4,
        'fail' => -2.4,
        'failed' => -2.5,
        'failure' => -2.5,
        'worst' => -3.0,
        'poor' => -2.0,
        'problem' => -1.8,
        'broken' => -2.4,
        'wrong' => -2.0,
        'disappointing' => -2.2,
        'disappointed' => -2.4,
        'fear' => -2.0,
        'scared' => -2.2,
        'fraud' => -3.0,
        'scam' => -3.0,
    ];

    /** Tokens inspected before a sentiment word for modifiers. */
    private const MODIFIER_WINDOW = 3;

    /** Multiplier applied when a sentiment word is negated. */
    private const NEGATION = -0.74;

    /** Multiplier applied when a sentiment word is intensified. */
    private const INTENSIFIER = 1.25;

    /** @var list<string> Words that invert a sentiment word. */
    private const NEGATORS = ['not', "don't", 'never', 'no'];

    /** @var list<string> Words that amplify a sentiment word. */
    private const INTENSIFIERS = ['very', 'really', 'extremely', 'absolutely'];

    /** Emphasis added per exclamation mark. */
    private const EXCLAMATION_WEIGHT = 0.05;

    /** Ceiling for exclamation emphasis. */
    private const EXCLAMATION_CAP = 0.5;

    /** Normalising constant of the compound score. */
    private const COMPOUND_NORMALISER = 15;

    /** Compound magnitude required to leave the neutral band. */
    private const LABEL_THRESHOLD = 0.05;

    /**
     * @param array<string, float> $lexicon Optional replacement lexicon.
     * @param list<string> $negators Optional replacement negation words.
     * @param list<string> $intensifiers Optional replacement intensifier words.
     */
    public function __construct(
        private readonly array $lexicon = [],
        private readonly array $negators = [],
        private readonly array $intensifiers = [],
    ) {
    }

    /**
     * @return array<string, float> Lexicon to score against.
     */
    private function lexicon(): array
    {
        return $this->lexicon !== [] ? $this->lexicon : self::DEFAULT_LEXICON;
    }

    /**
     * @return list<string> Words that invert a sentiment word.
     */
    private function negators(): array
    {
        return $this->negators !== [] ? $this->negators : self::NEGATORS;
    }

    /**
     * @return list<string> Words that amplify a sentiment word.
     */
    private function intensifiers(): array
    {
        return $this->intensifiers !== [] ? $this->intensifiers : self::INTENSIFIERS;
    }

    /**
     * Scores text for sentiment.
     *
     * @param string $text Raw input.
     * @return array{positive: float, negative: float, neutral: float, compound: float, label: string} Sentiment breakdown.
     */
    public function analyze(string $text): array
    {
        $lexicon = $this->lexicon();

        preg_match_all('/[\p{L}\p{N}]+|[!?]+/u', $text, $matches);
        $tokens = $matches[0] ?? [];

        $raw = number(0.0);
        $scores = [];

        foreach ($tokens as $index => $token) {
            $word = mb_strtolower($token);
            if (!isset($lexicon[$word])) {
                continue;
            }

            $score = number((float) $lexicon[$word]);

            foreach ($this->modifiers($tokens, $index) as $modifier) {
                $score = $score->times($modifier);
            }

            $raw = $raw->add($score);
            $scores[] = $score;
        }

        $raw = $raw->add($this->emphasis($text, $raw));
        $positive = $this->totalOf($scores, true);
        $negative = $this->totalOf($scores, false);
        $total = $positive->add($negative);
        $scored = $total->gt(0);
        $compound = $this->compound($raw);

        return [
            'positive' => $scored ? $this->share($positive, $total) : 0.0,
            'negative' => $scored ? $this->share($negative, $total) : 0.0,
            'neutral' => $scored ? 0.0 : 1.0,
            'compound' => $compound->round(4)->yield(),
            'label' => $this->label($compound),
        ];
    }

    /**
     * Collects modifier multipliers preceding a sentiment word.
     *
     * @param list<string> $tokens Every token in the input.
     * @param int $index Position of the sentiment word.
     * @return list<float> Multipliers to apply in order.
     */
    private function modifiers(array $tokens, int $index): array
    {
        $window = array_slice($tokens, max(0, $index - self::MODIFIER_WINDOW), self::MODIFIER_WINDOW);
        $modifiers = [];

        foreach ($window as $token) {
            $token = mb_strtolower($token);

            if (in_array($token, $this->negators(), true)) {
                $modifiers[] = self::NEGATION;
            }

            if (in_array($token, $this->intensifiers(), true)) {
                $modifiers[] = self::INTENSIFIER;
            }
        }

        return $modifiers;
    }

    /**
     * Adds emphasis for exclamation marks, following the running polarity.
     *
     * @param string $text Raw input.
     * @param Number $raw Score accumulated so far.
     * @return float Signed emphasis to add.
     */
    private function emphasis(string $text, Number $raw): float
    {
        $weight = number(min(self::EXCLAMATION_CAP, substr_count($text, '!') * self::EXCLAMATION_WEIGHT));

        return $raw->gt(0) ? $weight->yield() : $weight->negate()->yield();
    }

    /**
     * Sums the positive or negative portion of the scored tokens.
     *
     * @param list<Number> $scores Adjusted token scores.
     * @param bool $positive Whether to total the positive portion.
     * @return Number Absolute total of the selected portion.
     */
    private function totalOf(array $scores, bool $positive): Number
    {
        $total = number(0.0);

        foreach ($scores as $score) {
            if ($positive && $score->gt(0)) {
                $total = $total->add($score);
            }

            if (!$positive && $score->lt(0)) {
                $total = $total->add($score->negate());
            }
        }

        return $total;
    }

    /**
     * Expresses a portion as a rounded share of the total.
     *
     * @param Number $portion Positive or negative portion.
     * @param Number $total Combined magnitude of both portions.
     * @return float Share between 0 and 1.
     */
    private function share(Number $portion, Number $total): float
    {
        return $portion->parts($total)->round(4)->yield();
    }

    /**
     * Normalises the raw score into the compound range.
     *
     * @param Number $raw Score accumulated so far.
     * @return Number Compound score between -1 and 1.
     */
    private function compound(Number $raw): Number
    {
        $denominator = $raw->times($raw)->add(self::COMPOUND_NORMALISER)->raise(0.5);

        return $raw->parts($denominator);
    }

    /**
     * Maps a compound score onto a label.
     *
     * @param Number $compound Compound score.
     * @return string One of positive, negative, or neutral.
     */
    private function label(Number $compound): string
    {
        if ($compound->gte(self::LABEL_THRESHOLD)) {
            return 'positive';
        }

        return $compound->lte(-self::LABEL_THRESHOLD) ? 'negative' : 'neutral';
    }
}
