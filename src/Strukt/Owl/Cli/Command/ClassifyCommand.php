<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Classifier\NaiveBayesClassifier;
use Strukt\Owl\Cli\Command;

/**
 * @command classify
 * @description Classifies text with a stored model.
 * @usage classify <text> [options]
 *
 * @argument text
 * @description Text to classify, or a --file path when omitted.
 *
 * @option file
 * @description Read the text from this file.
 *
 * @option model
 * @default model.json
 * @description Path to the model file.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class ClassifyCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $data = json_decode((string) file_get_contents((string) $options['model']), true, 512, JSON_THROW_ON_ERROR);

        $this->respond(
            'classify',
            NaiveBayesClassifier::import($data)->predict($this->text($arguments, $options)),
            (bool) $options['json'],
        );

        return 0;
    }
}