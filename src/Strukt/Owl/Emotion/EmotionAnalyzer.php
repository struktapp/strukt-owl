<?php
declare (strict_types = 1);
namespace Strukt\Owl\Emotion;

use Strukt\Owl\Nlp\Text;

final class EmotionAnalyzer
{
    private array $lexicon = ['joy' => ['happy', 'joy', 'love', 'loved', 'excellent', 'wonderful', 'delighted', 'celebrate', 'win', 'winner', 'success', 'fun'], 'anger' => ['angry', 'anger', 'furious', 'hate', 'hated', 'rage', 'annoyed', 'outraged'], 'sadness' => ['sad', 'sadness', 'cry', 'crying', 'grief', 'lonely', 'loss', 'hurt', 'disappointed'], 'fear' => ['fear', 'afraid', 'scared', 'terrified', 'danger', 'threat', 'panic', 'worried'], 'surprise' => ['surprise', 'surprised', 'amazing', 'unexpected', 'suddenly', 'wow'], 'disgust' => ['disgusting', 'disgust', 'awful', 'horrible', 'nasty', 'gross']];

    public function analyze(string $text): array
    {
        $counts = array_fill_keys(array_keys($this->lexicon), 0);
        foreach (Text::words($text, true) as $w) {
            foreach ($this->lexicon as $e => $words) {
                if (in_array($w, $words, true)) {
                    $counts[$e]++;
                }
            }
        }

        $total = array_sum($counts);
        foreach ($counts as $e => $n) {
            $counts[$e] = $total ? round($n / $total, 4) : 0.0;
        }

        arsort($counts);
        return [
            'emotions' => $counts,
            'dominant' => array_key_first($counts),
            'matches'  => $total,
        ];
    }
}
