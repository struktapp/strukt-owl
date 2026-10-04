<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Nlp\KeywordExtractor;

/**
 * @command keywords
 * @description Extracts the top keywords from text.
 * @usage keywords <text> [options]
 *
 * @argument text
 * @description Text to analyze, or a --file path when omitted.
 *
 * @option file
 * @description Read the text from this file.
 *
 * @option limit
 * @type int
 * @default 10
 * @description How many keywords to return.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class KeywordsCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $this->respond('keywords', [
            'keywords' => (new KeywordExtractor())->extract(
                $this->text($arguments, $options),
                (int) $options['limit'],
            ),
        ], (bool) $options['json']);

        return 0;
    }
}