<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Config;
use App\Core\Http\Request;
use App\Enums\ERequestMethods;
use LogicException;

/**
 * HTTP Router
 *
 * Registers HTTP routes, matches requests, and dispatches handlers.
 * Supports static and dynamic routes, route caching, and error handling.
 */
final class Router
{
    /**
     * Static routes indexed by HTTP method and URI.
     *
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private array $staticRoutes = [];

    /**
     * Dynamic routes indexed by HTTP method and segment count.
     *
     * @var array<string, array<int, array<int, array{pattern: string, original: string, handler: array}>>>
     */
    private array $dynamicRoutes = [];

    /**
     * Cached matched routes.
     *
     * @var array<string, array{0: class-string, 1: string, 2?: array}>
     */
    private array $cachedMatches = [];

    /**
     * Cache file path for storing matched routes.
     *
     * @var string
     */
    private string $cacheFile;

    /**
     * @param RouteExecutor $executor Responsible for invoking controller handlers
     */
    public function __construct(private RouteExecutor $executor)
    {
        $this->cacheFile = Config::CACHE_DIR . 'routes.cache.php';
        $this->loadCache();
    }

    /**
     * Load cached matched routes from cache file.
     */
    private function loadCache(): void
    {
        if (file_exists($this->cacheFile)) {
            $cached = include $this->cacheFile;
            if (is_array($cached)) {
                $this->cachedMatches = $cached;
            }
        }
    }

    /**
     * Save matched routes to cache file.
     */
    private function saveCache(): void
    {
        if (!is_dir(Config::CACHE_DIR)) {
            mkdir(Config::CACHE_DIR, 0777, true);
        }

        $export = var_export($this->cachedMatches, true);
        $content = "<?php\n\nreturn {$export};\n";

        file_put_contents($this->cacheFile, $content, LOCK_EX);
    }

    /**
     * Add a route for the specified HTTP method.
     *
     * @param ERequestMethods $method HTTP method enum
     * @param string $uriPattern Route URI pattern (supports dynamic segments)
     * @param array{0: class-string, 1: string} $handler Controller class and method handler
     * @throws LogicException If route already exists
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $methodKey = $method->value;

        if (!str_contains($uriPattern, '{')) {
            if (isset($this->staticRoutes[$methodKey][$uriPattern])) {
                throw new LogicException("Static route already defined for {$methodKey} {$uriPattern}");
            }

            $this->staticRoutes[$methodKey][$uriPattern] = $handler;
        } else {
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

        $this->cachedMatches = [];
        $this->saveCache();
    }

    /**
     * Dispatch the current HTTP request to the appropriate route handler.
     *
     * @return void
     */
    public function dispatch(): void
    {
        $request = new Request();
        $uri = $request->getUri();
        $method = $request->getMethod();
        $methodEnum = ERequestMethods::tryFrom($method);

        if (!$methodEnum) {
            $this->handleMethodNotAllowed($method);
            return;
        }

        $methodKey = $methodEnum->value;
        $cacheKey = "{$methodKey}:{$uri}";

        if (isset($this->cachedMatches[$cacheKey])) {
            [$controllerClass, $methodName, $params] = $this->cachedMatches[$cacheKey];
            $this->executor->handle($controllerClass, $methodName, [...($params ?? []), $request]);
            return;
        }

        if (isset($this->staticRoutes[$methodKey][$uri])) {
            $handler = $this->staticRoutes[$methodKey][$uri];
            $this->cachedMatches[$cacheKey] = [$handler[0], $handler[1]];
            $this->saveCache();
            $this->executor->handle($handler[0], $handler[1], [$request]);
            return;
        }

        $segments = substr_count($uri, '/');
        foreach ($this->dynamicRoutes[$methodKey][$segments] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];
                $this->cachedMatches[$cacheKey] = [$handler[0], $handler[1], $params];
                $this->saveCache();
                $this->executor->handle($handler[0], $handler[1], [...$params, $request]);
                return;
            }
        }

        $this->handleNotFound($uri, $methodEnum);
    }

    /**
     * Handle requests with unsupported HTTP methods.
     *
     * @param string $method HTTP method used
     */
    protected function handleMethodNotAllowed(string $method): void
    {
        http_response_code(405);
        echo "<h1>405 Method Not Allowed</h1>";
        echo "<p>Method '{$method}' is not supported.</p>";
    }

    /**
     * Handle requests where no matching route was found.
     *
     * @param string $uri Requested URI
     * @param ERequestMethods $method HTTP method enum
     */
    protected function handleNotFound(string $uri, ERequestMethods $method): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>No route found for {$method->value} {$uri}</p>";

        if (Config::APP_DEBUG) {
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
     * List all registered routes, both static and dynamic.
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
}
