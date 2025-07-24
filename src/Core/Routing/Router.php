<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Contracts\RouterInterface;
use App\Core\AppConfig;
use App\Core\Http\Request;
use App\Enums\ERequestMethods;

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
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void
    {
        $methodKey = $method->value;

        if (!str_contains($uriPattern, '{')) {
            $this->staticRoutes[$methodKey][$uriPattern] = $handler;
        } else {
            $regex = '#^' . preg_replace('#/{(\w+)}#', '/(?<$1>[^/]+)', $uriPattern) . '$#';
            $segments = substr_count($uriPattern, '/');
            $this->dynamicRoutes[$methodKey][$segments][] = [
                'pattern' => $regex,
                'original' => $uriPattern,
                'handler' => $handler,
            ];
        }
    }

    /**
     * {@inheritdoc}
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
        $handler = $this->findRoute($methodKey, $uri);

        if (!$handler) {
            $this->handleNotFound($uri, $methodEnum);
            return;
        }

        [$controllerClass, $methodName, $params] = $handler;
        $this->executor->handle($controllerClass, $methodName, $params);
    }

    private function findRoute(string $method, string $uri): ?array
    {
        if (isset($this->staticRoutes[$method][$uri])) {
            $handler = $this->staticRoutes[$method][$uri];
            return [$handler[0], $handler[1], []];
        }

        $segments = substr_count($uri, '/');
        foreach ($this->dynamicRoutes[$method][$segments] ?? [] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler = $route['handler'];
                return [$handler[0], $handler[1], $params];
            }
        }

        return null;
    }

    private function loadRoutes(): void
    {
        if ($this->isCacheEnabled && file_exists($this->cacheFile)) {
            ['static' => $this->staticRoutes, 'dynamic' => $this->dynamicRoutes] = include $this->cacheFile;
        }
    }

    public function cacheRoutes(): void
    {
        if ($this->isCacheEnabled) {
            $export = var_export(['static' => $this->staticRoutes, 'dynamic' => $this->dynamicRoutes], true);
            file_put_contents($this->cacheFile, "<?php\n\nreturn {$export};\n", LOCK_EX);
        }
    }

    private function handleMethodNotAllowed(string $method): void
    {
        http_response_code(405);
        echo "<h1>405 Method Not Allowed</h1><p>Method '{$method}' is not supported.</p>";
    }

    private function handleNotFound(string $uri, ERequestMethods $method): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1><p>No route found for {$method->value} {$uri}</p>";
    }
}