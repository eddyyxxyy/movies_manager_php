<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\ERequestMethods;
use LogicException;
use Throwable;

/**
 * Router class handles registering and dispatching HTTP routes.
 * 
 * This implementation:
 * - Matches URI and method to controller actions.
 * - Passes Request object and route parameters to controller.
 * - Expects controller to return a Response object.
 * - Sends the Response after controller returns.
 */
class Router
{
    private array $routes = [];
    private bool $debug = false;

    public function enableDebug(): void
    {
        $this->debug = true;
    }

    /**
     * Registers a route pattern for a HTTP method.
     * 
     * @param ERequestMethods $method HTTP method enum
     * @param string $uriPattern Route pattern, e.g. /users/{id}
     * @param array{0: class-string, 1: string} $handler Controller class and method
     * 
     * @throws LogicException if duplicate route detected
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $regex = preg_replace('#/{(\w+)}#', '/(?<$1>[^/]+)', $uriPattern);
        $regex = '#^' . $regex . '$#';

        foreach ($this->routes[$method->value] ?? [] as $route) {
            if ($route['pattern'] === $regex) {
                throw new LogicException("Route already defined for {$method->value} {$uriPattern}");
            }
        }

        $this->routes[$method->value][] = [
            'pattern' => $regex,
            'original' => $uriPattern,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatches the incoming HTTP request.
     * Matches route and calls controller action.
     * Sends the response returned by the controller.
     */
    public function dispatch(): void
    {
        try {
            $request = new Request();
            $uri = $request->getUri();
            $method = $request->getMethod();

            $methodEnum = ERequestMethods::tryFrom($method);
            if (!$methodEnum) {
                $this->handleMethodNotAllowed($method);
                return;
            }

            $routes = $this->routes[$methodEnum->value] ?? [];

            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    [$controllerClass, $methodName] = $route['handler'];
                    $controller = new $controllerClass();

                    // Call controller with route params + request object
                    $response = call_user_func_array([$controller, $methodName], [
                        ...$params,
                        $request
                    ]);

                    if ($response instanceof Response) {
                        $response->send();
                    } else {
                        // Fallback for legacy controllers returning string/html directly
                        echo $response;
                    }
                    return;
                }
            }

            $this->handleNotFound($uri, $methodEnum);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * Print all registered routes (useful for debugging).
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
                    'handler' => "{$controller}::{$methodName}",
                ];
            }
        }

        return $result;
    }

    protected function handleMethodNotAllowed(string $method): void
    {
        http_response_code(405);
        echo "<h1>405 Method Not Allowed</h1>";
        echo "<p>Method '{$method}' is not supported.</p>";
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

    protected function handleServerError(Throwable $e): void
    {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
