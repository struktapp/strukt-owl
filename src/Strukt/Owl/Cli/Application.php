<?php
declare (strict_types = 1);
namespace Strukt\Owl\Cli;

use Strukt\Owl\Anomaly\AnomalyDetector;
use Strukt\Owl\Classifier\NaiveBayesClassifier;
use Strukt\Owl\Emotion\EmotionAnalyzer;
use Strukt\Owl\Language\LanguageDetector;
use Strukt\Owl\Nlp\KeywordExtractor;
use Strukt\Owl\Nlp\Readability;
use Strukt\Owl\Nlp\Summarizer;
use Strukt\Owl\Sentiment\SentimentAnalyzer;
use Strukt\Owl\Similarity\Similarity;
use Strukt\Owl\Spam\SpamDetector;

final class Application
{
    public function run(array $argv): int
    {
        $args = array_slice($argv, 1);
        if (! $args || in_array($args[0], ['--help', '-h'], true)) {
            $this->help();
            return 0;
        }

        $cmd = array_shift($args);
        try {

            $r = match ($cmd) {
                'sentiment'   => $this->sentiment($args),
                'emotion'     => $this->emotion($args),
                'keywords'    => $this->keywords($args),
                'summarize'   => $this->summarize($args),
                'language'    => $this->language($args),
                'similarity'  => $this->similarity($args),
                'match'       => $this->match($args),
                'classify'    => $this->classify($args),
                'train'       => $this->train($args),
                'spam'        => $this->spam($args),
                'readability' => $this->readability($args),
                'anomaly'     => $this->anomaly($args),
                '--version'   => ['version' => '0.1.0'],
                default       => throw new \InvalidArgumentException("Unknown command: $cmd")
            };

            $json = in_array('--json', $args, true) || in_array('--json', $argv, true);
            echo $json ? json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL : $this->format($cmd, $r);
            return 0;
        } catch (\Throwable $e) {
            fwrite(STDERR, "Error: {$e->getMessage()}\n");return 1;
        }
    }

    private function input(array $a): string
    {
        foreach ($a as $i => $x) {
            if ($x === '--file' && isset($a[$i + 1])) {
                return file_get_contents($a[$i + 1]) ?: '';
            }
        }

        foreach ($a as $x) {
            if (! str_starts_with($x, '--')) {
                return $x;
            }
        }

        return trim(stream_get_contents(STDIN));
    }

    private function sentiment($a)
    {
        return (new SentimentAnalyzer())->analyze($this->input($a));
    }

    private function emotion($a)
    {
        return (new EmotionAnalyzer())->analyze($this->input($a));
    }

    private function keywords($a)
    {
        $limit = $this->optionInt($a, 'limit', 10);
        return [
            'keywords' => (new KeywordExtractor())->extract($this->input($a), $limit),
        ];
    }

    private function summarize($a)
    {
        return [
            'summary' => (new Summarizer())->summarize($this->input($a), $this->optionInt($a, 'sentences', 3)),
        ];
    }

    private function language($a)
    {
        return (new LanguageDetector())->detect($this->input($a));
    }

    private function similarity($a)
    {
        $texts = array_values(array_filter($a, fn($x) => ! str_starts_with($x, '--')));
        if (count($texts) < 2) {
            throw new \InvalidArgumentException('similarity requires two texts.');
        }

        $s = new Similarity();
        return [
            'cosine'       => $s->cosine($texts[0], $texts[1]),
            'jaccard'      => $s->jaccard($texts[0], $texts[1]),
            'dice'         => $s->dice($texts[0], $texts[1]),
            'levenshtein'  => $s->levenshtein($texts[0], $texts[1]),
            'jaro_winkler' => $s->jaroWinkler($texts[0], $texts[1]),
        ];
    }

    private function match($a)
    {
        $x = array_values(array_filter($a, fn($v) => ! str_starts_with($v, '--')));
        if (count($x) < 2) {
            throw new \InvalidArgumentException('match requires a query and candidates.');
        }

        $s     = new Similarity();
        $best  = null;
        $score = -1;

        foreach (array_slice($x, 1) as $candidate) {
            $v = $s->jaroWinkler($x[0], $candidate);
            if ($v > $score) {
                $score = $v;
                $best  = $candidate;
            }
        }

        return [
            'query'     => $x[0],
            'match'     => $best,
            'score'     => round($score, 4),
            'algorithm' => 'jaro-winkler',
        ];
    }

    private function classify($a)
    {
        $file = $this->option($a, 'model', 'model.json');
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        return NaiveBayesClassifier::import($data)->predict($this->input($a));
    }

    private function train($a)
    {
        $class = $this->option($a, 'class');
        $model = $this->option($a, 'model', 'model.json');
        if (! $class) {
            throw new \InvalidArgumentException('--class is required.');
        }

        $c = file_exists($model) ? NaiveBayesClassifier::import(json_decode(file_get_contents($model), true)) : new NaiveBayesClassifier();

        $docs = [];
        foreach ($a as $x) {
            if (! str_starts_with($x, '--') && is_file($x)) {
                $docs[] = file_get_contents($x) ?: '';
            }
        }

        $c->train($class, $docs);
        file_put_contents($model, json_encode($c->export(), JSON_PRETTY_PRINT));
        return [
            'model'     => $model,
            'class'     => $class,
            'documents' => count($docs),
        ];
    }

    private function spam($a)
    {
        return (new SpamDetector())->analyze($this->input($a));
    }

    private function readability($a)
    {
        return (new Readability())->analyze($this->input($a));
    }

    private function anomaly($a)
    {
        $method    = $this->option($a, 'method', 'zscore');
        $threshold = (float) $this->option($a, 'threshold', '3');
        $text      = $this->input($a);
        $values    = preg_split('/[\s,;]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return (new AnomalyDetector())->detect($values, $method, $threshold);
    }

    private function option($a, $name, $default = null)
    {
        foreach ($a as $i => $x) {

            if ($x === "--$name" && isset($a[$i + 1])) {
                return $a[$i + 1];
            }

            if (str_starts_with($x, "--$name=")) {
                return substr($x, strlen($name) + 3);
            }
        }

        return $default;
    }

    private function optionInt($a, $n, $d)
    {
        return (int) $this->option($a, $n, $d);
    }

    private function format($cmd, $r)
    {
        $o = "PHP AI — $cmd\n────────────────────────────\n";
        foreach ($r as $k => $v) {
            if (is_array($v)) {
                $o .= "$k:\n";
                foreach ($v as $kk => $vv) {
                    $o .= "  $kk: " . (is_array($vv) ? json_encode($vv) : $vv) . "\n";
                }
            } else {
                $o .= str_replace('_', ' ', $k) . ": $v\n";
            }
        }

        return $o;
    }

    private function help(): void
    {
        echo "PHP AI — self-contained AI/NLP toolkit\n\nUsage: php-ai <command> [arguments] [options]\n\nCommands:\n  sentiment    Analyze sentiment\n  emotion      Detect emotions\n  keywords     Extract keywords\n  summarize    Summarize text\n  language     Detect language\n  similarity   Compare two texts\n  match        Find closest candidate\n  classify     Classify text\n  train        Train classifier\n  spam         Detect spam\n  readability  Analyze readability\n  anomaly      Detect numeric anomalies\n\nGlobal: --json  --help  --version\n";
    }
}
