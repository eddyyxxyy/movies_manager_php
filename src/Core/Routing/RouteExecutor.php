<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Http\Response;
use RuntimeException;

/**
 * Responsible for executing the route's controller and method,
 * then sending the HTTP response back to the client.
 */
final class RouteExecutor
{
    /**
     * @param ControllerResolver $resolver Resolves controller and method dependencies
     */
    public function __construct(private ControllerResolver $resolver)
    {
    }

    /**
     * Execute the specified controller method with optional parameters.
     *
     * @param class-string $controllerClass Fully qualified controller class name
     * @param string $method Method name to invoke
     * @param array $params Optional parameters passed (e.g., from route matches, requests)
     * @return void
     * @throws RuntimeException If method does not exist
     */
    public function handle(string $controllerClass, string $method, array $params = []): void
    {
        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        // Resolve and call controller method with dependency injection
        $response = $this->resolver->resolve($controllerClass, $method, $params);

        if ($response instanceof Response) {
            $response->send();
        } else {
            echo $response;
        }
    }
}
