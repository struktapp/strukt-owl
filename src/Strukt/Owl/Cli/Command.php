<?php

declare(strict_types=1);

namespace Strukt\Owl\Cli;

/**
 * Shared behavior for every Owl command.
 *
 * Concrete commands carry their own PHPDoc description and delegate the heavy
 * lifting to the toolkit, while this base resolves the text input and renders
 * the result as JSON or as a short plain text report.
 */
abstract class Command
{
    /**
     * Resolves the text to analyze from a file option, the text argument, or
     * standard input when both are absent.
     *
     * @param array<string, mixed> $arguments Positional arguments.
     * @param array<string, mixed> $options Options.
     * @return string Text to analyze.
     */
    protected function text(array $arguments, array $options): string
    {
        if (($options['file'] ?? null) !== null) {
            return (string) file_get_contents((string) $options['file']);
        }

        if (($arguments['text'] ?? null) !== null) {
            return (string) $arguments['text'];
        }

        return trim((string) stream_get_contents(STDIN));
    }

    /**
     * Prints a result either as JSON or as a short plain text report.
     *
     * @param string $command Command name, shown in the report header.
     * @param array<string, mixed> $result Command result.
     * @param bool $json Whether to render as JSON.
     */
    protected function respond(string $command, array $result, bool $json): void
    {
        echo $json
            ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
            : $this->format($command, $result);
    }

    /**
     * Renders a result as a short plain text report.
     *
     * @param string $command Command name, shown in the header.
     * @param array<string, mixed> $result Command result.
     * @return string Report text.
     */
    private function format(string $command, array $result): string
    {
        $report = "PHP AI — $command\n────────────────────────────\n";

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $report .= "$key:\n";

                foreach ($value as $nested => $nestedValue) {
                    $report .= '  ' . $nested . ': '
                        . (is_array($nestedValue) ? json_encode($nestedValue) : $nestedValue) . "\n";
                }

                continue;
            }

            $report .= str_replace('_', ' ', $key) . ": $value\n";
        }

        return $report;
    }
}