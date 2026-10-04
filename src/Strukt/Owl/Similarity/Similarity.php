<?php

declare(strict_types=1);

namespace Strukt\Owl\Similarity;

use Strukt\Matrix;
use Strukt\Number;
use Strukt\Owl\Nlp\Text;

/**
 * String and term similarity measures.
 *
 * The term-frequency methods are expressed with the Strukt matrix and number
 * primitives: cosine similarity is a real vector product, and the set
 * measures reduce to a ratio of two counts. The character-level Jaro-Winkler
 * implementation stays index based because its matching window is inherently
 * sequential.
 */
final class Similarity
{
    /** Longest common prefix credited by Jaro-Winkler. */
    private const PREFIX_LIMIT = 4;

    /** Weight applied to the common prefix. */
    private const PREFIX_WEIGHT = 0.1;

    /**
     * Cosine similarity between the term vectors of two texts.
     *
     * @param string $a First text.
     * @param string $b Second text.
     * @return float Similarity between 0 and 1.
     */
    public function cosine(string $a, string $b): float
    {
        $left = Text::termFrequencies(Text::words($a, true));
        $right = Text::termFrequencies(Text::words($b, true));

        $terms = array_keys($left + $right);
        if ($terms === []) {
            return 0.0;
        }

        $u = new Matrix([array_map(static fn (string $t): int => $left[$t] ?? 0, $terms)]);
        $v = new Matrix([array_map(static fn (string $t): int => $right[$t] ?? 0, $terms)]);

        $dot = $this->product($u, $v);
        $leftMagnitude = $this->product($u, $u);
        $rightMagnitude = $this->product($v, $v);

        if ($leftMagnitude == 0 || $rightMagnitude == 0) {
            return 0.0;
        }

        $denominator = number($leftMagnitude)->times($rightMagnitude)->raise(0.5);

        return number($dot)->parts($denominator)->yield();
    }

    /**
     * Jaccard similarity of the two term sets.
     *
     * @param string $a First text.
     * @param string $b Second text.
     * @return float Similarity between 0 and 1; 1 when both sets are empty.
     */
    public function jaccard(string $a, string $b): float
    {
        $left = array_unique(Text::words($a, true));
        $right = array_unique(Text::words($b, true));
        $union = count(array_unique([...$left, ...$right]));

        return $union > 0
            ? number(count(array_intersect($left, $right)))->parts($union)->yield()
            : 1.0;
    }

    /**
     * Dice coefficient of the two term sets.
     *
     * @param string $a First text.
     * @param string $b Second text.
     * @return float Similarity between 0 and 1; 1 when both sets are empty.
     */
    public function dice(string $a, string $b): float
    {
        $left = array_unique(Text::words($a, true));
        $right = array_unique(Text::words($b, true));
        $total = count($left) + count($right);

        return $total > 0
            ? number(2 * count(array_intersect($left, $right)))->parts($total)->yield()
            : 1.0;
    }

    /**
     * Multibyte-aware edit distance between two strings.
     *
     * @param string $a First string.
     * @param string $b Second string.
     * @return int Number of single-character edits needed.
     */
    public function levenshtein(string $a, string $b): int
    {
        return levenshtein(mb_strtolower($a), mb_strtolower($b));
    }

    /**
     * Jaro-Winkler similarity between two strings.
     *
     * @param string $a First string.
     * @param string $b Second string.
     * @return float Similarity between 0 and 1.
     */
    public function jaroWinkler(string $a, string $b): float
    {
        $a = mb_strtolower($a);
        $b = mb_strtolower($b);

        if ($a === $b) {
            return 1.0;
        }

        if ($a === '' || $b === '') {
            return 0.0;
        }

        $lengthA = mb_strlen($a);
        $lengthB = mb_strlen($b);

        $radius = number(max($lengthA, $lengthB))
            ->parts(2)
            ->round(0, PHP_ROUND_HALF_DOWN)
            ->subtract(1);
        $window = (int) $radius->lt(0) ? 0 : (int) $radius->yield();

        $matchedA = array_fill(0, $lengthA, false);
        $matchedB = array_fill(0, $lengthB, false);
        $matches = 0;

        for ($i = 0; $i < $lengthA; $i++) {
            for ($j = max(0, $i - $window); $j <= min($i + $window, $lengthB - 1); $j++) {
                if (!$matchedB[$j] && mb_substr($a, $i, 1) === mb_substr($b, $j, 1)) {
                    $matchedA[$i] = true;
                    $matchedB[$j] = true;
                    $matches++;
                    break;
                }
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        $jaro = $this->jaro($a, $b, $matchedA, $matchedB, $matches, $lengthA, $lengthB);

        $prefix = 0;
        while (
            $prefix < min(self::PREFIX_LIMIT, $lengthA, $lengthB)
            && mb_substr($a, $prefix, 1) === mb_substr($b, $prefix, 1)
        ) {
            $prefix++;
        }

        $boost = number($prefix)->times(self::PREFIX_WEIGHT)->times(number(1)->subtract($jaro));

        return $jaro->add($boost)->yield();
    }

    /**
     * Multiplies two term vectors and returns the scalar product.
     *
     * A term vector is a 1 x n matrix, so the product of the row with the
     * transpose of the other is a 1 x 1 matrix holding the dot product.
     *
     * @param Matrix $u First term vector.
     * @param Matrix $v Second term vector.
     * @return int|float Dot product of the two vectors.
     */
    private function product(Matrix $u, Matrix $v): int|float
    {
        return $u->multiply($v->transpose())->yield()[0][0];
    }

    /**
     * Computes the Jaro similarity from a completed match window.
     *
     * @param string $a First string.
     * @param string $b Second string.
     * @param array<int, bool> $matchedA Matched positions in the first string.
     * @param array<int, bool> $matchedB Matched positions in the second string.
     * @param int $matches Number of matched characters.
     * @param int $lengthA Length of the first string.
     * @param int $lengthB Length of the second string.
     * @return \Strukt\Number Jaro similarity.
     */
    private function jaro(
        string $a,
        string $b,
        array $matchedA,
        array $matchedB,
        int $matches,
        int $lengthA,
        int $lengthB,
    ): Number {
        $transpositions = $this->transpositions($a, $b, $matchedA, $matchedB) / 2;

        return number($matches)
            ->parts($lengthA)
            ->add(number($matches)->parts($lengthB))
            ->add(number($matches - $transpositions)->parts($matches))
            ->parts(3);
    }

    /**
     * Counts half-transpositions in the matched subsequences.
     *
     * @param string $a First string.
     * @param string $b Second string.
     * @param array<int, bool> $matchedA Matched positions in the first string.
     * @param array<int, bool> $matchedB Matched positions in the second string.
     * @return int Number of mismatched pairs in the matched subsequences.
     */
    private function transpositions(string $a, string $b, array $matchedA, array $matchedB): int
    {
        $charactersA = $this->matchedCharacters($a, $matchedA);
        $charactersB = $this->matchedCharacters($b, $matchedB);

        $count = 0;
        foreach ($charactersA as $index => $character) {
            if ($character !== $charactersB[$index]) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Extracts the matched characters of a string in position order.
     *
     * @param string $value String to read.
     * @param array<int, bool> $matched Matched positions.
     * @return list<string> Matched single characters.
     */
    private function matchedCharacters(string $value, array $matched): array
    {
        $characters = [];

        foreach ($matched as $index => $isMatched) {
            if ($isMatched) {
                $characters[] = mb_substr($value, $index, 1);
            }
        }

        return $characters;
    }
}
