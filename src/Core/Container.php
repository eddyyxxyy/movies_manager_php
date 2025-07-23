<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

/**
 * A simple but powerful Dependency Injection Container.
 * Its job is to create ("resolve") objects and their dependencies automatically.
 */
final class Container
{
    /** @var array<class-string, Closure> Has the instructions for creating objects. */
    private array $bindings = [];

    /**
     * Binds a "creator" function to a class name.
     * When we ask for the class, the function will run.
     *
     * @param string $id The class name (e.g., Connection::class)
     * @param Closure $closure The function that creates the object.
     */
    public function bind(string $id, Closure $closure): void
    {
        $this->bindings[$id] = $closure;
    }

    /**
     * Creates and returns an instance of the requested class.
     *
     * @template T
     * @param class-string<T> $id The class name to resolve.
     * @return T An instance of the class.
     */
    public function resolve(string $id): object
    {
        // Checks if the recipe for the object already exists
        if (isset($this->bindings[$id])) {
            return $this->bindings[$id]($this);
        }

        // If theres no recipe, creates it with reflection
        $reflectionClass = new ReflectionClass($id);
        if (!$reflectionClass->isInstantiable()) {
            throw new RuntimeException("Class {$id} is not instantiable.");
        }

        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            // Returns a new instance if no constructor
            return new $id();
        }

        // Inspect its parameters if the class has a constructor
        $parameters = $constructor->getParameters();
        $dependencies = array_map(
            function (ReflectionParameter $param) use ($id) {
                $type = $param->getType();
                if (!$type) {
                    throw new RuntimeException("Cannot resolve {$id}: Param '{$param->name}' is missing a type hint.");
                }
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    // Recursively resolve each dependency
                    return $this->resolve($type->getName());
                }
                throw new RuntimeException("Cannot resolve {$id}: Param '{$param->name}' has an unsupported or missing type hint.");
            },
            $parameters
        );

        // Create a new instance of the class with its resolved dependencies
        return $reflectionClass->newInstanceArgs($dependencies);
    }
}