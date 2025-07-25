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
        // Log a warning or throw an exception in a real application
        // For simple dev setup, silently returning might be okay.
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        // Handle error if file() fails
        return;
    }

    foreach ($lines as $line) {
        // Ignore comments and empty lines
        $line = trim($line);
        if (str_starts_with($line, '#') || $line === '') {
            continue;
        }

        // Split the line into name and value
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            // Skip lines that don't conform to KEY=VALUE format
            continue;
        }

        [$name, $value] = $parts;
        $name = trim($name);
        $value = trim($value);

        // Check if the value is enclosed in single or double quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            // Remove the surrounding quotes
            $value = substr($value, 1, -1);

            // Handle escaped quotes within the value (e.g., "value with \"quotes\"")
            $value = str_replace(['\"', "\'"], ['"', "'"], $value);
        }

        // Prevent overwriting existing environment variables
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}