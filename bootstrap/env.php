<?php

declare(strict_types=1);

/**
 * Loads environment variables from a .env file into getenv(), $_ENV, and $_SERVER.
 *
 * This is a simple implementation for development. For production, it's recommended
 * to use a more robust library like `vlucas/phpdotenv` or manage environment
 * variables at the server level.
 *
 * @param string $path The path to the .env file.
 * @return void
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}