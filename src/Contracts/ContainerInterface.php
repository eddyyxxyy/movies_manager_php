<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for a Dependency Injection Container.
 *
 * Defines the contract for a service container that can manage class
 * dependencies, bindings, and singleton instances.
 */
interface ContainerInterface
{
    /**
     * Register a binding in the container.
     *
     * @param class-string $abstract Abstract class or interface name.
     * @param class-string|Closure|object|null $concrete Concrete class name, closure factory, or instance.
     * @return void
     */
    public function bind(string $abstract, string|object|null $concrete = null): void;

    /**
     * Register a singleton instance in the container.
     *
     * @param class-string $abstract Abstract class or interface name.
     * @param class-string|Closure|object|null $concrete Concrete class name, closure factory, or instance.
     * @return void
     */
    public function singleton(string $abstract, string|object|null $concrete = null): void;

    /**
     * Resolve a class or interface to an instance.
     *
     * @param class-string $id Class or interface name to resolve.
     * @return object Resolved instance.
     */
    public function resolve(string $id): object;

    /**
     * Call a callable or method with automatic dependency injection.
     *
     * @param callable|string|array $callable The callable to execute.
     * @param array<string, mixed> $overrideParams Associative array of parameters to override by name.
     * @return mixed Return value of the callable.
     */
    public function call(callable|string|array $callable, array $overrideParams = []): mixed;
}