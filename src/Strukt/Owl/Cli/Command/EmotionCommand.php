<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli\Command;

use Strukt\Console\Runtime\CommandInterface;
use Strukt\Owl\Cli\Command;
use Strukt\Owl\Emotion\EmotionAnalyzer;

/**
 * @command emotion
 * @description Detects the emotions expressed in text.
 * @usage emotion <text> [options]
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
class EmotionCommand extends Command implements CommandInterface
{
    public function execute(array $arguments, array $options): int
    {
        $this->respond(
            'emotion',
            (new EmotionAnalyzer())->analyze($this->text($arguments, $options)),
            (bool) $options['json'],
        );

        return 0;
    }
}