<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Request;
use App\Enums\ERequestMethods;
use LogicException;
use Throwable;

/**
 * Core routing class responsible for registering and dispatching HTTP routes.
 */
class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $staticRoutes = [];

    /** @var array<string, array<int, array<int, array{pattern: string, original: string, handler: array}>>> */
    private array $dynamicRoutes = [];

    /** @var array<string, array{0: class-string, 1: string, 2?: array}> */
    private array $cachedMatches = [];

    private bool $debug = false;

    /**
     * Enables verbose debug mode for development.
     */
    public function enableDebug(): void
    {
        $this->debug = true;
    }

    /**
     * Registers a route for a given HTTP method.
     *
     * @param ERequestMethods $method HTTP verb (GET, POST, etc.)
     * @param string $uriPattern Route URI pattern. Can include dynamic segments (e.g. /users/{id})
     * @param array{0: class-string, 1: string} $handler Controller class and method
     *
     * @throws LogicException If route is already defined
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $methodKey = $method->value;

        if (!str_contains($uriPattern, '{')) {
            // Static route
            if (isset($this->staticRoutes[$methodKey][$uriPattern])) {
                throw new LogicException("Static route already defined for {$methodKey} {$uriPattern}");
            }

            $this->staticRoutes[$methodKey][$uriPattern] = $handler;
        } else {
            // Dynamic route: convert pattern to regex
            $regex = preg_replace('#/{(\w+)}#', '/(?<$1>[^/]+)', $uriPattern);
            $regex = '#^' . $regex . '$#';
            $segments = substr_count($uriPattern, '/');

            foreach ($this->dynamicRoutes[$methodKey][$segments] ?? [] as $route) {
                if ($route['pattern'] === $regex) {
                    throw new LogicException("Dynamic route already defined for {$methodKey} {$uriPattern}");
                }
            }

            $this->dynamicRoutes[$methodKey][$segments][] = [
                'pattern' => $regex,
                'original' => $uriPattern,
                'handler' => $handler,
            ];
        }
    }

    /**
     * Dispatches the current request to the matched route, if any.
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

            $methodKey = $methodEnum->value;

            // Fast path: check cached route match
            $cacheKey = "{$methodKey}:{$uri}";
            if (isset($this->cachedMatches[$cacheKey])) {
                [$controllerClass, $methodName, $params] = $this->cachedMatches[$cacheKey];
                (new RouteExecutor())->handle($controllerClass, $methodName, [...($params ?? []), $request]);
                return;
            }

            // Static route
            if (isset($this->staticRoutes[$methodKey][$uri])) {
                $handler = $this->staticRoutes[$methodKey][$uri];
                $this->cachedMatches[$cacheKey] = [$handler[0], $handler[1]];
                (new RouteExecutor())->handle($handler[0], $handler[1], [$request]);
                return;
            }

            // Dynamic route
            $segments = substr_count($uri, '/');
            foreach ($this->dynamicRoutes[$methodKey][$segments] ?? [] as $route) {
                if (preg_match($route['pattern'], $uri, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $handler = $route['handler'];
                    $this->cachedMatches[$cacheKey] = [$handler[0], $handler[1], $params];
                    (new RouteExecutor())->handle($handler[0], $handler[1], [...$params, $request]);
                    return;
                }
            }

            $this->handleNotFound($uri, $methodEnum);
        } catch (Throwable $e) {
            $this->handleServerError($e);
        }
    }

    /**
     * Lists all registered routes, both static and dynamic.
     *
     * @return array<string, array<array{uri: string, handler: string}>>
     */
    public function listRoutes(): array
    {
        $result = [];

        foreach ($this->staticRoutes as $method => $routes) {
            foreach ($routes as $uri => $handler) {
                [$controller, $methodName] = $handler;
                $result[$method][] = [
                    'uri' => $uri,
                    'handler' => "$controller::$methodName",
                ];
            }
        }

        foreach ($this->dynamicRoutes as $method => $groups) {
            foreach ($groups as $routes) {
                foreach ($routes as $route) {
                    [$controller, $methodName] = $route['handler'];
                    $result[$method][] = [
                        'uri' => $route['original'],
                        'handler' => "$controller::$methodName",
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Handles unsupported HTTP methods.
     */
    protected function handleMethodNotAllowed(string $method): void
    {
        http_response_code(405);
        echo "<h1>405 Method Not Allowed</h1>";
        echo "<p>Method '{$method}' is not supported.</p>";
    }

    /**
     * Handles unmatched URIs.
     */
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

    /**
     * Handles unexpected server errors.
     */
    protected function handleServerError(Throwable $e): void
    {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}
