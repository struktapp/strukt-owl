# Emotions

`Strukt\Owl\Emotion\EmotionAnalyzer` measures how much of six named emotions a
text expresses by counting word-group matches, then converting the counts to
shares.

```php
$result = (new EmotionAnalyzer())->analyze('I am so happy and thrilled!');

$result['emotions']     // ['happy' => ..., 'anger' => 0.0, ...]
$result['dominant']     // 'happy'
$result['matches']      // total matched words
```

## The emotion groups

Six lexicons of emotion words are matched per token (stop words removed):

| Emotion   | Example words            |
|-----------|--------------------------|
| happy     | joy, happy, thrilled ... |
| anger     | angry, furious, rage ... |
| sadness   | sad, grief, blue ...     |
| fear      | afraid, terror, dread ...|
| surprise  | wow, shock, amazed ...   |
| disgust   | gross, vile, revulsion ... |

## Shares

For each emotion the number of matching words is counted. Each count is
converted to a share of the total matches:

```
total = Σ count(e)                       # all six emotions
share(e) = count(e) / total              # 0.0 when total is 0

dominant = argmax share(e)               # null when no matches
```

Shares are rounded to 4 decimal places; `dominant` is the emotion with the
largest share, and `matches` is the raw total count so you can see the
confidence in the reading.

The constructor accepts a replacement group list — the same override pattern
as [sentiment](sentiment.md#lexicon) and the other lexicons.