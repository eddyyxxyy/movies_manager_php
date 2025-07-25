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
 * and its method's dependencies, then ensures a proper HTTP response is returned.
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
     * Provides access to the underlying Dependency Injection Container.
     * This is primarily used by the Router to resolve middleware instances.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Executes the specified controller method with injected dependencies.
     * This method ensures that the final output is an HTTP Response object.
     *
     * @param class-string $controllerClass Fully qualified controller class name.
     * @param string $method Method name to invoke.
     * @param array<string, mixed> $params Parameters from the route match (e.g., dynamic URL segments).
     * @return Response The HTTP response from the controller method.
     * @throws RuntimeException If the controller method does not exist or returns an invalid type.
     */
    public function handle(string $controllerClass, string $method, array $params = []): Response
    {
        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        // Call the controller method with dependency injection
        $response = $this->container->call([$controllerClass, $method], $params);

        // Ensure the controller method always returns an instance of Response
        if ($response instanceof Response) {
            return $response;
        } elseif (is_string($response) || is_numeric($response)) {
            return Response::html((string) $response);
        }

        throw new RuntimeException(
            "Controller method {$controllerClass}::{$method} must return an instance of " .
            Response::class . " or a string/numeric. Returned type: " . get_debug_type($response)
        );
    }
}