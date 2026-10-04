<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Anomaly\AnomalyDetector;
use Strukt\Owl\Cli\Command;

/**
 * @command anomaly
 * @description Detects anomalies in a list of numbers.
 * @usage anomaly <values> [options]
 *
 * @argument text
 * @description Numbers separated by spaces, commas, or semicolons.
 *
 * @option file
 * @description Read the numbers from this file.
 *
 * @option method
 * @default zscore
 * @enum zscore,iqr
 * @description Detection method.
 *
 * @option threshold
 * @type float
 * @default 3
 * @description Sensitivity threshold.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class AnomalyCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $values = preg_split('/[\s,;]+/', trim($this->text($arguments, $options)), -1, PREG_SPLIT_NO_EMPTY);

        $this->respond('anomaly', (new AnomalyDetector())->detect(
            $values,
            (string) $options['method'],
            (float) $options['threshold'],
        ), (bool) $options['json']);

        return 0;
    }
}