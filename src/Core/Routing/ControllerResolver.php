<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use ReflectionMethod;
use RuntimeException;

/**
 * Resolves controllers and their method dependencies.
 *
 * Uses the Container to instantiate controllers and inject dependencies automatically.
 */
final class ControllerResolver
{
    public function __construct(private Container $container)
    {
    }

    /**
     * Resolve a controller instance and call its method with injected dependencies.
     *
     * @param class-string $controllerClass Fully qualified controller class name
     * @param string $method Method name to invoke
     * @param array $params Parameters passed to the method (e.g. route params)
     * @return mixed Return value of the controller method
     * @throws RuntimeException If dependencies cannot be resolved or method does not exist
     */
    public function resolve(string $controllerClass, string $method, array $params = []): mixed
    {
        $controller = $this->container->resolve($controllerClass);

        $reflection = ReflectionMethod::createFromMethodName("$controllerClass::$method");

        $args = [];

        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();

            // If parameter matches route param, use it
            if (array_key_exists($paramName, $params)) {
                $args[] = $params[$paramName];
                continue;
            }

            if (
                !$paramType ||
                !($paramType instanceof \ReflectionNamedType) ||
                $paramType->isBuiltin()
            ) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new RuntimeException("Cannot resolve parameter \${$paramName} for {$controllerClass}::{$method}");
                }
            } else {
                $args[] = $this->container->resolve($paramType->getName());
            }
        }

        return $controller->{$method}(...$args);
    }
}
