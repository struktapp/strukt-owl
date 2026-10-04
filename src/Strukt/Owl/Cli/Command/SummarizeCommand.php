<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Nlp\Summarizer;

/**
 * @command summarize
 * @description Summarizes text into a few sentences.
 * @usage summarize <text> [options]
 *
 * @argument text
 * @description Text to analyze, or a --file path when omitted.
 *
 * @option file
 * @description Read the text from this file.
 *
 * @option sentences
 * @type int
 * @default 3
 * @description How many sentences to return.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class SummarizeCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $this->respond('summarize', [
            'summary' => (new Summarizer())->summarize(
                $this->text($arguments, $options),
                (int) $options['sentences'],
            ),
        ], (bool) $options['json']);

        return 0;
    }
}