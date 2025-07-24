<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Contracts\ContainerInterface;
use App\Core\Http\Response;
use RuntimeException;

/**
 * Executes a route's controller action.
 *
 * This class uses the service container to resolve the controller
 * and its method's dependencies, then sends the resulting response.
 */
final class RouteExecutor
{
    /**
     * @param ContainerInterface $container The service container.
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Execute the specified controller method with injected dependencies.
     *
     * @param class-string $controllerClass Fully qualified controller class name.
     * @param string $method Method name to invoke.
     * @param array<string, mixed> $params Parameters from the route match.
     * @return void
     */
    public function handle(string $controllerClass, string $method, array $params = []): void
    {
        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        $response = $this->container->call([$controllerClass, $method], $params);

        if ($response instanceof Response) {
            $response->send();
        } elseif (is_string($response) || is_numeric($response)) {
            echo $response;
        }
    }
}