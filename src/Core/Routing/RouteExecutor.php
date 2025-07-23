<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Http\Response;
use RuntimeException;
use Throwable;

/**
 * Handles the execution of route handlers and response delivery.
 */
final class RouteExecutor
{
    /**
     * Dependency Injection Container
     * @var Container
     */
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Instantiates the controller and invokes the specified method.
     *
     * @param class-string $controllerClass Controller class name
     * @param string $methodName Method to call on the controller
     * @param array<int|string, mixed> $params Parameters to pass (e.g. route params, request)
     */
    public function handle(string $controllerClass, string $methodName, array $params = []): void
    {
        try {
            $controller = $this->container->resolve($controllerClass);

            if (!method_exists($controller, $methodName)) {
                throw new RuntimeException("Method '{$methodName}' not found in '{$controllerClass}'");
            }

            $response = call_user_func_array([$controller, $methodName], $params);
            $this->sendResponse($response);
        } catch (Throwable $e) {
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    }

    /**
     * Sends an HTTP response to the client.
     *
     * @param mixed $response Either a Response object or string
     */
    private function sendResponse(mixed $response): void
    {
        if ($response instanceof Response) {
            $response->send();
        } else {
            echo $response;
        }
    }
}
