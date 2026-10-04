<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Similarity\Similarity;

/**
 * @command match
 * @description Finds the candidate closest to a query.
 * @usage match <query> <candidates> [options]
 *
 * @argument query
 * @type string
 * @required
 * @description Query text.
 *
 * @argument candidates
 * @description Candidates separated by commas or spaces.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class MatchCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $candidates = preg_split('/[\s,;]+/', trim((string) $arguments['candidates']), -1, PREG_SPLIT_NO_EMPTY);

        if ($candidates === []) {
            throw new \InvalidArgumentException('match requires a query and candidates.');
        }

        $similarity = new Similarity();
        $best = null;
        $score = -1.0;

        foreach ($candidates as $candidate) {
            $value = $similarity->jaroWinkler($arguments['query'], $candidate);

            if ($value > $score) {
                $score = $value;
                $best = $candidate;
            }
        }

        $this->respond('match', [
            'query' => $arguments['query'],
            'match' => $best,
            'score' => number($score)->round(4)->yield(),
            'algorithm' => 'jaro-winkler',
        ], (bool) $options['json']);

        return 0;
    }
}