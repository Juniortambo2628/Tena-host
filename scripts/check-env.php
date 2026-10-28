<?php

/**
 * Validates a .env file with the same parser Laravel uses (vlucas/phpdotenv)
 * and reports each bad entry by line number and key name, never by value, so
 * it is safe to run against production secrets in CI logs.
 *
 * Usage: php scripts/check-env.php path/to/.env [path/to/vendor/autoload.php]
 */

use Dotenv\Parser\Lines;
use Dotenv\Parser\Parser;

$file = $argv[1] ?? null;
$autoload = $argv[2] ?? __DIR__.'/../vendor/autoload.php';

if (! $file || ! is_readable($file)) {
    fwrite(STDERR, "Usage: php scripts/check-env.php <env-file> [autoload.php]\n");
    exit(2);
}

require $autoload;

$content = file_get_contents($file);
if (str_starts_with($content, "\xEF\xBB\xBF")) {
    $content = substr($content, 3);
}

$lines = preg_split("/(\r\n|\n|\r)/", $content);
$entries = Lines::process($lines);
$parser = new Parser();
$errors = [];
$cursor = 0;

foreach ($entries as $entry) {
    // Map the entry back to the line it starts on.
    $first = strtok($entry, "\n");
    for ($i = $cursor; $i < count($lines); $i++) {
        if (str_starts_with(ltrim($lines[$i]), ltrim($first))) {
            $cursor = $i + 1;
            break;
        }
    }
    $lineNo = $cursor;

    try {
        $parser->parse($entry);
    } catch (Throwable $e) {
        $reason = preg_replace('/ at \[.*\]\.?$/s', '', $e->getMessage());
        $name = strstr($entry, '=', true);
        $label = $name !== false && preg_match('/^\s*(export\s+)?[A-Za-z0-9_.]+\s*$/', $name)
            ? 'key '.trim(preg_replace('/^\s*export\s+/', '', $name))
            : 'text with no valid KEY= in front (a value that wrapped onto its own line?)';
        $errors[] = "line {$lineNo}: {$label}: {$reason}";
    }
}

if ($errors) {
    fwrite(STDERR, "Invalid .env (".count($errors)." problem(s)):\n  ".implode("\n  ", $errors)."\n");
    fwrite(STDERR, "Fix: keep each KEY=value on one line, use only A-Z 0-9 _ in names, and wrap values containing spaces or # in double quotes.\n");
    exit(1);
}

echo 'OK: '.count($entries)." entries parsed.\n";
