<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Classifier\NaiveBayesClassifier;
use Strukt\Owl\Cli\Command;

/**
 * @command train
 * @description Trains the classifier from documents and stores the model.
 * @usage train --class=<label> [options]
 *
 * @argument documents
 * @description Space or comma separated paths to training documents.
 *
 * @option class
 * @type string
 * @required
 * @description Class label for the documents.
 *
 * @option model
 * @default model.json
 * @description Where to store the trained model.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class TrainCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $class = (string) $options['class'];
        $model = (string) $options['model'];

        $classifier = file_exists($model)
            ? NaiveBayesClassifier::import(json_decode((string) file_get_contents($model), true))
            : new NaiveBayesClassifier();

        $documents = [];

        foreach (preg_split('/[\s,;]+/', trim((string) ($arguments['documents'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) as $path) {
            if (is_file($path)) {
                $documents[] = (string) file_get_contents($path);
            }
        }

        $classifier->train($class, $documents);
        file_put_contents($model, json_encode($classifier->export(), JSON_PRETTY_PRINT));

        $this->respond('train', [
            'model' => $model,
            'class' => $class,
            'documents' => count($documents),
        ], (bool) $options['json']);

        return 0;
    }
}