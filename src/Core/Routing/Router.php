<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Contracts\RouterInterface;
use App\Core\AppConfig;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Enums\ERequestMethods;
use App\Contracts\MiddlewareInterface;

/**
 * HTTP Router
 *
 * Registers HTTP routes, matches requests, and dispatches handlers.
 */
final class Router implements RouterInterface
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $staticRoutes = [];

    /** @var array<string, array<int, array<int, array{pattern: string, original: string, handler: array}>>> */
    private array $dynamicRoutes = [];

    /**
     * Stores middleware classes (FQCNs) associated with static routes.
     * @var array<string, array<string, class-string<MiddlewareInterface>[]>>
     */
    private array $staticRouteMiddlewares = []; // New property for static route middlewares

    /**
     * Stores middleware classes (FQCNs) associated with dynamic routes.
     * @var array<string, array<int, array<int, class-string<MiddlewareInterface>[]>>>
     */
    private array $dynamicRouteMiddlewares = [];

    private string $cacheFile;
    private bool $isCacheEnabled;

    public function __construct(
        private RouteExecutor $executor,
        private AppConfig $config
    ) {
        $this->cacheFile = $this->config->get('cache_dir') . 'routes.cache.php';
        $this->isCacheEnabled = !$this->config->isDebug();
        $this->loadRoutes();
    }

    /**
     * {@inheritdoc}
     * Adds a new route to the router. The $handler can now include an optional third element: an array of middleware FQCNs.
     * E.g., `$router->add(GET, '/', [HomeController::class, 'index', [AuthMiddleware::class]])`
     *
     * @param ERequestMethods $method The HTTP method.
     * @param string $uriPattern The URI pattern (can contain dynamic segments like '{id}').
     * @param array $handler An array containing: [0] Controller FQCN, [1] Method Name, [2, optional] Array of Middleware FQCNs.
     * @return void
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $methodKey = $method->value;
        $middlewares = [];

        // Check if the handler includes middlewares (the third element in the $handler array)
        if (isset($handler[2]) && is_array($handler[2])) {
            $middlewares = $handler[2];
            // Re-assign handler to just controller and method, as middlewares are extracted
            $handler = [$handler[0], $handler[1]];
        }

        if (!str_contains($uriPattern, '{')) {
            // Static route
            $this->staticRoutes[$methodKey][$uriPattern] = $handler;
            $this->staticRouteMiddlewares[$methodKey][$uriPattern] = $middlewares;
        } else {
            // Dynamic route
            $regex = '#^' . preg_replace('#/{(\w+)}#', '/(?<$1>[^/]+)', $uriPattern) . '$#';
            $segments = substr_count($uriPattern, '/');
            $this->dynamicRoutes[$methodKey][$segments][] = [
                'pattern' => $regex,
                'original' => $uriPattern,
                'handler' => $handler,
                'middlewares' => $middlewares, // Store middlewares for dynamic route
            ];
        }
    }

    /**
     * {@inheritdoc}
     * Dispatches the current HTTP request to the appropriate route handler.
     * This method now builds and executes a middleware pipeline before calling the controller.
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
        $routeInfo = $this->findRoute($methodKey, $uri);

        if (!$routeInfo) {
            $this->handleNotFound($uri, $methodEnum);
            return;
        }

        [$controllerClass, $methodName, $params, $middlewares] = $routeInfo;

        $finalHandler = function (Request $req) use ($controllerClass, $methodName, $params): Response {
            return $this->executor->handle($controllerClass, $methodName, $params);
        };

        // Build the middleware pipeline using array_reduce.
        $pipeline = array_reduce(
            array_reverse($middlewares), // Process middlewares in reverse order
            function (callable $next, string $middlewareClass): callable {
                /** @var MiddlewareInterface $middlewareInstance */
                $middlewareInstance = $this->executor->getContainer()->resolve($middlewareClass);
                return fn(Request $req) => $middlewareInstance->process($req, $next);
            },
            $finalHandler
        );

        // Execute the entire middleware pipeline with the initial request.
        $response = $pipeline($request);
        // Send the final HTTP response that emerged from the pipeline.
        $response->send();
    }

    /**
     * Tries to find a route that matches the given method and URI.
     *
     * @param string $method The HTTP method (string).
     * @param string $uri The request URI.
     * @return array|null An array containing [controllerClass, methodName, params, middlewares] if found, or null.
     */
    private function findRoute(string $method, string $uri): ?array
    {
        // Find static
        if (isset($this->staticRoutes[$method][$uri])) {
            $handler = $this->staticRoutes[$method][$uri];
            $middlewares = $this->staticRouteMiddlewares[$method][$uri] ?? []; // Get middlewares for this static route
            return [$handler[0], $handler[1], [], $middlewares]; // Return handler, empty params, and middlewares
        }

        // Find dynamic
        $segments = substr_count($uri, '/');
        foreach ($this->dynamicRoutes[$method][$segments] ?? [] as $route) {
            // Try to match the URI against the route's regex pattern.
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Filter the `preg_match` results to get only the named captured parameters.
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];
                $middlewares = $route['middlewares'] ?? []; // Get middlewares for this dynamic route
                return [$handler[0], $handler[1], $params, $middlewares]; // Return handler, extracted params, and middlewares
            }
        }
        return null; // No matching route found.
    }

    /**
     * Saves the current routes (static, dynamic, and their associated middlewares) to a cache file.
     * This is typically done once after all routes are defined (e.g., via a CLI command, or during bootstrap
     * if not in debug mode) to optimize performance in production.
     *
     * @return void
     */
    public function cacheRoutes(): void
    {
        if ($this->isCacheEnabled) {
            $export = var_export([
                'static' => $this->staticRoutes,
                'dynamic' => $this->dynamicRoutes,
                'static_middlewares' => $this->staticRouteMiddlewares, // Include static middlewares in cache
                'dynamic_middlewares' => $this->dynamicRouteMiddlewares // Include dynamic middlewares in cache (though already part of dynamicRoutes entry)
            ], true);

            file_put_contents($this->cacheFile, "<?php\n\nreturn {$export};\n", LOCK_EX);
        }
    }

    /**
     * Loads cached routes from a file if caching is enabled and the file exists.
     * This method is called during the router's construction.
     *
     * @return void
     */
    private function loadRoutes(): void
    {
        if ($this->isCacheEnabled && file_exists($this->cacheFile)) {
            [
                'static' => $this->staticRoutes,
                'dynamic' => $this->dynamicRoutes,
                'static_middlewares' => $this->staticRouteMiddlewares,
                'dynamic_middlewares' => $this->dynamicRouteMiddlewares
            ] = include $this->cacheFile;
        }
    }

    /**
     * Handles a 404 Not Found error.
     * Displays a generic 404 message.
     * TODO: This should ideally render a proper view file for consistent styling.
     *
     * @param string $uri The requested URI.
     * @param ERequestMethods $method The HTTP method used.
     * @return void (sends the response and exits script implicitly)
     */
    private function handleNotFound(string $uri, ERequestMethods $method): void
    {
        $response = Response::html("<h1>404 Not Found</h1><p>The page '{$uri}' you requested with method '{$method->value}' was not found.</p>", 404);
        $response->send();
    }

    /**
     * Handles a 405 Method Not Allowed error.
     * Displays a generic 405 message.
     * TODO: This should ideally render a proper view file for consistent styling.
     *
     * @param string $method The disallowed HTTP method.
     * @return void (sends the response and exits script implicitly)
     */
    private function handleMethodNotAllowed(string $method): void
    {
        $response = Response::html("<h1>405 Method Not Allowed</h1><p>The method '{$method}' is not allowed for this URL.</p>", 405);
        $response->send();
    }
}