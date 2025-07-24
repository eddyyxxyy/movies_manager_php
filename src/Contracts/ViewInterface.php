<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for the View renderer.
 *
 * Defines the contract for rendering PHP views and partials,
 * providing variable isolation and HTML escaping.
 */
interface ViewInterface
{
    /**
     * Renders a view file with optional layout.
     *
     * @param string $viewPath Path relative to the views folder, e.g., 'home/index'.
     * @param array<string, mixed> $params Variables to be extracted and used inside the view.
     * @param string|null $layout Optional layout name relative to views/layouts.
     * @return string The rendered HTML output.
     */
    public function render(string $viewPath, array $params = [], ?string $layout = 'layout'): string;

    /**
     * Renders a reusable partial/component and returns its HTML.
     *
     * @param string $partial Partial filename relative to /partials/, without .php extension.
     * @param array<string, mixed> $params Optional variables to extract inside the partial.
     * @return string Rendered HTML of the partial.
     */
    public function renderPartial(string $partial, array $params = []): string;

    /**
     * Escapes a string for safe output in HTML.
     *
     * @param string $string The string to escape.
     * @return string The escaped string.
     */
    public static function e(string $string): string;
}