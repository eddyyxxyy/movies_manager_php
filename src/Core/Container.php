<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ContainerInterface;
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
final class Container implements ContainerInterface
{
    /**
     * Bindings array.
     * @var array<class-string, array{concrete: Closure|class-string|object, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * Singleton instances cache.
     * @var array<class-string, object>
     */
    protected array $instances = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, string|object|null $concrete = null): void
    {
        $this->registerBinding($abstract, $concrete, false);
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, string|object|null $concrete = null): void
    {
        $this->registerBinding($abstract, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $isShared = isset($this->bindings[$id]) && $this->bindings[$id]['shared'];
        $concrete = $this->bindings[$id]['concrete'] ?? $id;

        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } elseif (is_object($concrete)) {
            $object = $concrete;
        } elseif ($concrete === $id) {
            $object = $this->autowire($id);
        } else {
            $object = $this->resolve($concrete);
        }

        if ($isShared) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function call(callable|string|array $callable, array $overrideParams = []): mixed
    {
        $reflection = match (true) {
            $callable instanceof Closure => new ReflectionFunction($callable),
            is_string($callable) && str_contains($callable, '::') => new ReflectionMethod($callable),
            is_array($callable) => new ReflectionMethod($callable[0], $callable[1]),
            default => new ReflectionFunction($callable)
        };

        $dependencies = [];
        $callableTarget = is_array($callable) ? (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]) . '::' . $callable[1] : $reflection->getName();

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $overrideParams)) {
                $dependencies[] = $overrideParams[$name];
                continue;
            }
            $dependencies[] = $this->resolveParameter($param, $callableTarget);
        }

        if ($reflection instanceof ReflectionMethod) {
            $instance = is_object($callable[0]) ? $callable[0] : $this->resolve($callable[0]);
            return $reflection->invokeArgs($instance, $dependencies);
        }

        return $reflection->invokeArgs($dependencies);
    }

    /**
     * Register a binding in the container.
     */
    private function registerBinding(string $abstract, string|object|null $concrete, bool $shared): void
    {
        $concrete ??= $abstract;
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Autowire a class by resolving its constructor dependencies recursively.
     */
    private function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $class();
        }

        $dependencies = array_map(
            fn(ReflectionParameter $param) => $this->resolveParameter($param, $class . '::__construct'),
            $constructor->getParameters()
        );

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve an individual method or constructor parameter.
     */
    private function resolveParameter(ReflectionParameter $param, string $context): mixed
    {
        $type = $param->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->resolve($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new RuntimeException("Unresolvable dependency: Cannot resolve parameter \${$param->getName()} for {$context}");
    }
}