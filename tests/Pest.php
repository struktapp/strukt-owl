<?php

/** Pest configuration shared by the package test suite. */

use PHPUnit\Framework\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Returns the text of the bundled sample article.
 *
 * Readability and keyword assertions need a document long enough to have
 * several sentences, so the suite ships one rather than repeating a fixture
 * string in every test.
 */
function article(): string
{
    return (string) file_get_contents(dirname(__DIR__) . '/article.txt');
}
