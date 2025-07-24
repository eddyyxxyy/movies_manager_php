<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Contracts\ViewInterface;
use App\Core\AppConfig;
use RuntimeException;
use Throwable;

/**
 * Renders PHP views and partials with optional layouts.
 */
class View implements ViewInterface
{
    private string $viewsDir;

    public function __construct(AppConfig $config)
    {
        $this->viewsDir = rtrim($config->get('views_dir'), '/\\') . '/';
    }

    /**
     * {@inheritdoc}
     */
    public static function e(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $viewPath, array $params = [], ?string $layout = 'base-layout'): string
    {
        $viewPath = $this->sanitizePath($viewPath);
        $content = $this->renderView($viewPath, $params);

        if ($layout === null) {
            return $content;
        }

        $layoutPath = $this->sanitizePath("layouts/{$layout}");
        return $this->renderView($layoutPath, array_merge($params, ['content' => $content]));
    }

    /**
     * {@inheritdoc}
     */
    public function renderPartial(string $partial, array $params = []): string
    {
        $partialPath = $this->sanitizePath('partials/' . $partial);
        return $this->renderView($partialPath, $params);
    }

    /**
     * Internal helper method to render a PHP view file.
     */
    private function renderView(string $viewPath, array $params): string
    {
        $viewFile = $this->viewsDir . $viewPath . '.php';

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
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering view {$viewFile}: " . $e->getMessage(), 0, $e);
        }

        return ob_get_clean() ?: '';
    }

    /**
     * Sanitizes a path to prevent directory traversal.
     */
    private function sanitizePath(string $path): string
    {
        $path = str_replace(['\\'], '/', $path);
        $segments = explode('/', $path);
        $clean = array_filter($segments, fn($seg) => $seg !== '' && $seg !== '.' && $seg !== '..');
        return implode('/', $clean);
    }
}