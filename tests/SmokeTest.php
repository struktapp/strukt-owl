<?php
declare (strict_types = 1);
namespace Tests;

use PHPUnit\Framework\TestCase;
use Strukt\Owl\Sentiment\SentimentAnalyzer;
use Strukt\Owl\Similarity\Similarity;

final class SmokeTest extends TestCase
{
    public function testPositiveSentiment(): void
    {
        $r = (new SentimentAnalyzer())->analyze('This is absolutely fantastic!');
        $this->assertSame('positive', $r['label']);
    }
    public function testCosineIdentity(): void
    {
        $this->assertSame(1.0, (new Similarity())->cosine('hello world', 'hello world'));
    }
}
