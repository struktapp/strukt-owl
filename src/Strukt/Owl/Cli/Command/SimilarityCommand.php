<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Similarity\Similarity;

/**
 * @command similarity
 * @description Compares two texts with several similarity measures.
 * @usage similarity <first> <second> [options]
 *
 * @argument first
 * @type string
 * @required
 * @description First text.
 *
 * @argument second
 * @type string
 * @required
 * @description Second text.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class SimilarityCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $similarity = new Similarity();

        $this->respond('similarity', [
            'cosine' => $similarity->cosine($arguments['first'], $arguments['second']),
            'jaccard' => $similarity->jaccard($arguments['first'], $arguments['second']),
            'dice' => $similarity->dice($arguments['first'], $arguments['second']),
            'levenshtein' => $similarity->levenshtein($arguments['first'], $arguments['second']),
            'jaro_winkler' => $similarity->jaroWinkler($arguments['first'], $arguments['second']),
        ], (bool) $options['json']);

        return 0;
    }
}