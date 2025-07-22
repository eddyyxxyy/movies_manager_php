<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\ERequestMethods;
use LogicException;

/**
 * Router class responsible for registering and dispatching HTTP routes.
 */
class Router
{
    /**
     * Registered route patterns organized by HTTP method.
     *
     * @var array<string, array<int, array{pattern: string, original: string, handler: array}>>
     */
    private array $routes = [];

    /**
     * @var bool Enables route debug mode (prints route table on 404).
     */
    private bool $debug = false;

    /**
     * Enables route debug mode.
     */
    public function enableDebug(): void
    {
        $this->debug = true;
    }

    /**
     * Registers a new route.
     *
     * @param ERequestMethods $method HTTP method
     * @param string $uriPattern Pattern like /user/{id}
     * @param array{0: class-string, 1: string} $handler Controller and method
     *
     * @throws LogicException If the route is already defined
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $regex = preg_replace('#/{(\w+)}#', '/(?<$1>[^/]+)', $uriPattern);
        $regex = '#^' . $regex . '$#';

        // Prevent duplicate route definitions
        foreach ($this->routes[$method->value] ?? [] as $route) {
            if ($route['pattern'] === $regex) {
                throw new LogicException("Route already defined for {$method->value} {$uriPattern}");
            }
        }

        $this->routes[$method->value][] = [
            'pattern' => $regex,
            'original' => $uriPattern,
            'handler' => $handler
        ];
    }

    /**
     * Dispatches the current HTTP request to a matching route.
     */
    public function dispatch(): void
    {
        try {
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            $methodEnum = ERequestMethods::tryFrom($requestMethod);

            if (!$methodEnum) {
                $this->handleMethodNotAllowed($requestMethod);
                return;
            }

            $routes = $this->routes[$methodEnum->value] ?? [];

            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $requestUri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    [$controllerClass, $method] = $route['handler'];
                    $controller = new $controllerClass();

                    call_user_func_array([$controller, $method], $params);
                    return;
                }
            }

            $this->handleNotFound($requestUri, $methodEnum);
        } catch (\Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * Lists all registered routes by method.
     *
     * @return array<string, array<int, array{uri: string, handler: string}>>
     */
    public function listRoutes(): array
    {
        $result = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                [$controller, $methodName] = $route['handler'];
                $result[$method][] = [
                    'uri' => $route['original'],
                    'handler' => "{$controller}::{$methodName}"
                ];
            }
        }

        return $result;
    }

    protected function handleMethodNotAllowed(string $invalidMethod): void
    {
        http_response_code(405);
        echo "<h1>405 Method Not Allowed</h1>";
        echo "<p>Method '{$invalidMethod}' is not supported.</p>";
    }

    protected function handleNotFound(string $uri, ERequestMethods $method): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>No route found for {$method->value} {$uri}</p>";

        if ($this->debug) {
            echo "<h2>Available Routes:</h2><ul>";
            foreach ($this->listRoutes() as $method => $routes) {
                foreach ($routes as $route) {
                    echo "<li><code>{$method} {$route['uri']}</code> → {$route['handler']}</li>";
                }
            }
            echo "</ul>";
        }
    }

    protected function handleServerError(\Throwable $e): void
    {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
