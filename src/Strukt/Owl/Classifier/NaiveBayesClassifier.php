<?php

declare(strict_types=1);

namespace Strukt\Owl\Classifier;

use Strukt\Number;
use Strukt\Owl\Nlp\Text;

/**
 * Multinomial naive Bayes classifier over word counts.
 *
 * Training tallies documents, per-class token counts, and the shared
 * vocabulary. Prediction multiplies the class prior by the smoothed
 * likelihood of every token, then normalises the resulting log scores with a
 * softmax so the reported scores are proper probabilities.
 */
final class NaiveBayesClassifier
{
    /** Documents seen per class. */
    private array $docs = [];

    /** Token counts per class. */
    private array $counts = [];

    /** Total tokens seen per class. */
    private array $totals = [];

    /** Distinct tokens seen across all classes. */
    private array $vocab = [];

    /**
     * Trains the classifier on a batch of documents.
     *
     * @param string $class Class label to attach to the documents.
     * @param array<int, mixed> $documents Documents belonging to the class.
     */
    public function train(string $class, array $documents): void
    {
        foreach ($documents as $document) {
            $this->docs[$class] = ($this->docs[$class] ?? 0) + 1;

            foreach (Text::words((string) $document, true) as $word) {
                $this->counts[$class][$word] = ($this->counts[$class][$word] ?? 0) + 1;
                $this->totals[$class] = ($this->totals[$class] ?? 0) + 1;
                $this->vocab[$word] = 1;
            }
        }
    }

    /**
     * Predicts the most likely class for a text.
     *
     * @param string $text Raw input.
     * @return array{class: string, confidence: float, scores: array<string, float>} Prediction with per-class probabilities.
     *
     * @throws \LogicException When the classifier has not been trained.
     */
    public function predict(string $text): array
    {
        if ($this->docs === []) {
            throw new \LogicException('Classifier has not been trained.');
        }

        $tokens = Text::words($text, true);
        $total = number(array_sum($this->docs));
        $vocabulary = max(1, count($this->vocab));
        $scores = [];

        foreach ($this->docs as $class => $documents) {
            $prior = number($documents)->parts($total);
            $score = number(log($prior->yield()));
            $tokensInClass = $this->totals[$class] ?? 0;

            foreach ($tokens as $word) {
                $numerator = number(($this->counts[$class][$word] ?? 0) + 1);
                $denominator = number($tokensInClass + $vocabulary);
                $likelihood = $numerator->parts($denominator);

                $score = $score->add(number(log($likelihood->yield())));
            }

            $scores[$class] = $score->yield();
        }

        $peak = max($scores);
        $probabilities = [];
        $mass = number(0.0);

        foreach ($scores as $class => $score) {
            $probability = number(exp($score - $peak));
            $probabilities[$class] = $probability;
            $mass = $mass->add($probability);
        }

        foreach ($probabilities as $class => $probability) {
            $probabilities[$class] = $probability->parts($mass);
        }

        arsort($probabilities);
        $best = (string) array_key_first($probabilities);

        return [
            'class' => $best,
            'confidence' => $probabilities[$best]->round(4)->yield(),
            'scores' => array_map(
                static fn (Number $probability): float => $probability->round(4)->yield(),
                $probabilities,
            ),
        ];
    }

    /**
     * Exports the trained model.
     *
     * @return array{docs: array<string, int>, counts: array<string, array<string, int>>, totals: array<string, int>, vocab: list<string>} Serialisable model.
     */
    public function export(): array
    {
        return [
            'docs' => $this->docs,
            'counts' => $this->counts,
            'totals' => $this->totals,
            'vocab' => array_keys($this->vocab),
        ];
    }

    /**
     * Rebuilds a classifier from an exported model.
     *
     * @param array<string, mixed> $data Model produced by export.
     * @return self Restored classifier.
     */
    public static function import(array $data): self
    {
        $classifier = new self();
        $classifier->docs = $data['docs'] ?? [];
        $classifier->counts = $data['counts'] ?? [];
        $classifier->totals = $data['totals'] ?? [];

        foreach ($data['vocab'] ?? [] as $word) {
            $classifier->vocab[$word] = 1;
        }

        return $classifier;
    }
}
