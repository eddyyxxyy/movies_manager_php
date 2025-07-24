<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

/**
 * Dependency Injection Container
 *
 * Automatically resolves class dependencies and manages singleton instances.
 */
final class Container
{
    /**
     * Bindings array.
     * Maps abstract identifiers (interfaces or classes) to concrete implementations or closures.
     *
     * @var array<class-string, Closure|class-string|object>
     */
    protected array $bindings = [];

    /**
     * Singleton instances cache.
     *
     * @var array<class-string, object>
     */
    protected array $instances = [];

    /**
     * Register a binding in the container.
     *
     * @param class-string $abstract Abstract class or interface name
     * @param class-string|Closure|object $concrete Concrete class name, closure factory, or instance
     */
    public function bind(string $abstract, string|object $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a singleton instance in the container.
     *
     * @param class-string $abstract Abstract class or interface name
     * @param object $instance Instantiated singleton object
     */
    public function singleton(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a class or interface to an instance.
     *
     * If a singleton exists, returns it.
     * If a binding exists, uses it.
     * Otherwise tries to autowire via constructor injection.
     *
     * @param class-string $id Class or interface name to resolve
     * @return object Resolved instance
     * @throws RuntimeException If resolution fails
     */
    public function resolve(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id];

            if ($concrete instanceof Closure) {
                $object = $concrete($this);
            } elseif (is_string($concrete)) {
                $object = $this->resolve($concrete);
            } else {
                $object = $concrete;
            }

            if (is_object($object)) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        return $this->autowire($id);
    }

    /**
     * Autowire a class by resolving its constructor dependencies recursively.
     *
     * @param class-string $class Fully qualified class name
     * @return object Instantiated object
     * @throws RuntimeException If dependency cannot be resolved
     */
    protected function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = array_map(fn(ReflectionParameter $param) => $this->resolveParameter($param), $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Call a callable or method with automatic dependency injection.
     *
     * Supports callables as:
     * - Closure or function name (string)
     * - Array with [class/object, method]
     * - Static method string "Class::method"
     *
     * @param callable|string|array $callable The callable to execute
     * @param array<string, mixed> $overrideParams Associative array of parameters to override by name
     * @return mixed Return value of the callable
     * @throws RuntimeException If dependency cannot be resolved
     */
    public function call(callable|string|array $callable, array $overrideParams = []): mixed
    {
        if (is_array($callable)) {
            $className = is_object($callable[0]) ? get_class($callable[0]) : $callable[0];
            $methodName = $callable[1];
            $reflection = ReflectionMethod::createFromMethodName("$className::$methodName");
        } elseif (is_string($callable) && str_contains($callable, '::')) {
            $reflection = ReflectionMethod::createFromMethodName($callable);
        } else {
            $reflection = new ReflectionFunction($callable);
        }

        $dependencies = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $overrideParams)) {
                $dependencies[] = $overrideParams[$name];
                continue;
            }

            $dependencies[] = $this->resolveParameter($param);
        }

        return call_user_func_array($callable, $dependencies);
    }

    /**
     * Resolve an individual method or constructor parameter.
     *
     * If type is class, resolves it recursively.
     * If optional, returns default value.
     * Otherwise throws an exception.
     *
     * @param ReflectionParameter $param The parameter reflection
     * @return mixed Resolved parameter value
     * @throws RuntimeException
     */
    protected function resolveParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();
            return $this->resolve($typeName);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException("Unable to resolve parameter \${$param->getName()}.");
    }
}
