<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;
use RuntimeException;

/**
 * Class responsible for rendering PHP views and partials with optional layouts,
 * providing variable isolation and HTML escaping helpers.
 */
class View
{
    /**
     * Escapes a string for safe output in HTML.
     *
     * @param string $string The string to escape.
     * @return string The escaped string.
     */
    public static function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Renders a view file with optional layout.
     *
     * The $viewPath is relative to the configured views directory,
     * without the .php extension.
     *
     * @param string $viewPath Path relative to the views folder, e.g., 'home/index'
     * @param array<string, mixed> $params Variables to be extracted and used inside the view
     * @param string|null $layout Optional layout name relative to views/layouts, or null for no layout
     *
     * @return string The rendered HTML output.
     *
     * @throws RuntimeException If the view or layout file is not found.
     */
    public static function render(string $viewPath, array $params = [], ?string $layout = 'layout'): string
    {
        $viewPath = self::sanitizePath($viewPath);
        $content = self::renderView($viewPath, $params);

        if ($layout === null) {
            return $content;
        }

        $layout = self::sanitizePath("layouts/{$layout}");

        return self::renderView($layout, array_merge($params, ['content' => $content]));
    }

    /**
     * Renders a reusable partial/component and returns its HTML.
     *
     * Partials are stored inside the 'partials' directory inside views.
     *
     * @param string $partial Partial filename relative to /partials/, without .php extension
     * @param array<string, mixed> $params Optional variables to extract inside the partial
     *
     * @return string Rendered HTML of the partial.
     *
     * @throws RuntimeException If the partial file is not found.
     */
    public static function renderPartial(string $partial, array $params = []): string
    {
        $partial = self::sanitizePath('partials/' . $partial);
        return self::renderView($partial, $params);
    }

    /**
     * Internal helper method to render a PHP view file.
     *
     * Extracts variables safely and isolates scope using a closure.
     * Provides a local `$e` helper function for HTML escaping inside views.
     *
     * @param string $viewPath Path relative to views directory, without extension
     * @param array<string, mixed> $params Variables to extract inside the view
     *
     * @return string Rendered HTML output.
     *
     * @throws RuntimeException If the view file does not exist.
     */
    private static function renderView(string $viewPath, array $params): string
    {
        $viewFile = Config::VIEWS_DIR . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        ob_start();

        try {
            (function () use ($viewFile, $params) {
                extract($params, EXTR_SKIP);
                $e = [self::class, 'e'];
                include $viewFile;
            })();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering view {$viewFile}: " . $e->getMessage(), 0, $e);
        }

        return ob_get_clean();
    }

    /**
     * Sanitizes the provided view or partial path to prevent directory traversal attacks.
     *
     * Removes sequences like "../" or absolute paths to ensure the path stays within views.
     *
     * @param string $path The input path to sanitize.
     * @return string The sanitized relative path.
     */
    private static function sanitizePath(string $path): string
    {
        $path = str_replace(['\\'], '/', $path); // Normalize separators
        $segments = explode('/', $path);
        $clean = array_filter($segments, fn($seg) => $seg !== '' && $seg !== '.' && $seg !== '..');
        return implode('/', $clean);
    }

}
