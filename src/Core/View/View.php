<?php

declare(strict_types=1);

namespace App\Core\View;

use RuntimeException;

class View
{
    /**
     * Render a view with optional layout.
     *
     * @param string $viewPath Path relative to views folder, ex: 'home'
     * @param array<string, mixed> $params Variables to inject in the view
     * @param string|null $layout Layout view name, ex: 'layout' or null for none
     *
     * @return string Rendered HTML
     */
    public static function render(string $viewPath, array $params = [], ?string $layout = 'layout'): string
    {
        // Render the main view content first
        $content = self::renderView($viewPath, $params);

        // If layout is null, return content directly
        if ($layout === null) {
            return $content;
        }

        // Render the layout, passing content as a variable
        return self::renderView("layouts/{$layout}", array_merge($params, ['content' => $content]));
    }

    /**
     * Helper to render a PHP view file with given params.
     *
     * @param string $viewPath Path relative to views folder, ex: 'home'
     * @param array<string, mixed> $params
     *
     * @return string
     *
     * @throws RuntimeException If the partial file does not exist
     */
    private static function renderView(string $viewPath, array $params): string
    {
        $viewFile = __DIR__ . '/../../View/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View file not found: {$viewFile}");
        }

        // Extract params to variables for use inside view
        extract($params);

        // Start output buffering to capture HTML
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    /**
     * Render a reusable partial/component and return the HTML string.
     *
     * @param string $partial Partial filename (without .php), relative to /View/partials/
     * @param array<string, mixed> $params Optional parameters to be extracted into scope
     *
     * @return string The rendered HTML of the partial
     *
     * @throws RuntimeException If the partial file does not exist
     */
    public static function renderPartial(string $partial, array $params = []): string
    {
        $file = __DIR__ . '/../../View/partials/' . $partial . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Partial not found: {$file}");
        }

        extract($params, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean();
    }
}
