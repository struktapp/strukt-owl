<?php

/** Coverage for the command line front end. */

use Strukt\Owl\Cli\Application;

/**
 * Runs a command and captures what it writes to standard output.
 *
 * @param list<string> $arguments Command arguments.
 * @return string Captured output.
 */
function owl(array $arguments): string
{
    return owlRun($arguments)['output'];
}

/**
 * Runs a command and reports both its exit code and output.
 *
 * @param list<string> $arguments Command arguments.
 * @return array{status: int, output: string} Exit code and captured output.
 */
function owlRun(array $arguments): array
{
    ob_start();

    try {
        $status = (new Application())->run(array_merge(['owl'], $arguments));
    } finally {
        $output = (string) ob_get_clean();
    }

    return ['status' => $status, 'output' => $output];
}

test('no arguments lists the available commands', function (): void {
    expect(owl([]))->toContain('Available commands:')
        ->and(owl(['--help']))->toContain('Available commands:')
        ->and(owl(['-h']))->toContain('Available commands:');
});

test('the banner lists every command the application accepts', function (): void {
    $banner = owl([]);

    foreach (['sentiment', 'emotion', 'keywords', 'summarize', 'language', 'similarity', 'match', 'classify', 'train', 'spam', 'readability', 'anomaly', 'version'] as $command) {
        expect($banner)->toContain($command);
    }
});

test('the version command reports the release', function (): void {
    expect(json_decode(owl(['version', '--json']), true))->toBe(['version' => '0.1.0']);
});

test('sentiment is reported as json on request', function (): void {
    $result = json_decode(owl(['sentiment', 'This is terrible and broken, I hate it!!!', '--json']), true);

    expect($result['label'])->toBe('negative')
        ->and($result['compound'])->toEqual(-0.9109);
});

test('a command without json prints a plain text report', function (): void {
    $output = owl(['sentiment', 'I love this']);

    expect($output)->toContain('PHP AI — sentiment')
        ->and($output)->toContain('label: positive')
        ->and($output)->not->toContain('{');
});

test('keywords are limited by the limit option', function (): void {
    $result = json_decode(owl(['keywords', 'The quick brown fox jumps over the lazy dog. The fox is quick.', '--limit=3', '--json']), true);

    expect($result['keywords'])->toHaveCount(3);
});

test('summaries honour the sentences option', function (): void {
    $result = json_decode(owl(['summarize', 'One. Two two! Three? Four', '--sentences', '2', '--json']), true);

    expect($result['summary'])->toBeString();
});

test('similarity fails when fewer than two texts are given', function (): void {
    $result = owlRun(['similarity', 'only one text', '--json']);

    expect($result['status'])->toBe(1)
        ->and($result['output'])->toBe('');
});

test('similarity compares the two given texts', function (): void {
    $result = json_decode(owl(['similarity', 'hello world', 'hello world', '--json']), true);

    expect($result['cosine'])->toEqual(1.0)
        ->and(array_keys($result))->toBe(['cosine', 'jaccard', 'dice', 'levenshtein', 'jaro_winkler']);
});

test('match reports the closest candidate', function (): void {
    $result = json_decode(owl(['match', 'marhta', 'MARHTA banana', '--json']), true);

    expect($result['match'])->toBe('MARHTA')
        ->and($result['score'])->toEqual(1.0)
        ->and($result['algorithm'])->toBe('jaro-winkler');
});

test('anomaly splits numbers and honours the method option', function (): void {
    $result = json_decode(owl(['anomaly', '1,2,3,4,5,6,7,8,9,1000', '--method', 'iqr', '--json']), true);

    expect($result['method'])->toBe('iqr')
        ->and($result['anomalies'])->toBe([['index' => 9, 'value' => 1000]]);
});

test('an unknown command fails with a non zero exit code', function (): void {
    expect(owlRun(['definitely-not-a-command'])['status'])->toBe(1);
});

test('a successful command exits with a zero status', function (): void {
    expect(owlRun(['sentiment', 'I love this', '--json'])['status'])->toBe(0);
});

test('training without a class label is rejected', function (): void {
    expect(owlRun(['train', '--model', '/tmp/owl-nope.json'])['status'])->toBe(1);
});