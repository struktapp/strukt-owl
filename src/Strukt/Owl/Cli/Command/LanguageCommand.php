<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Language\LanguageDetector;

/**
 * @command language
 * @description Detects the language of text.
 * @usage language <text> [options]
 *
 * @argument text
 * @description Text to analyze, or a --file path when omitted.
 *
 * @option file
 * @description Read the text from this file.
 *
 * @option json
 * @flag
 * @description Render the result as JSON.
 */
class LanguageCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $this->respond(
            'language',
            (new LanguageDetector())->detect($this->text($arguments, $options)),
            (bool) $options['json'],
        );

        return 0;
    }
}