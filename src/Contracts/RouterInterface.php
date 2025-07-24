<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ERequestMethods;

/**
 * Interface for the HTTP Router.
 *
 * Defines the contract for registering routes and dispatching
 * an incoming HTTP request to the appropriate handler.
 */
interface RouterInterface
{
    /**
     * Add a route for the specified HTTP method.
     *
     * @param ERequestMethods $method HTTP method enum.
     * @param string $uriPattern Route URI pattern (supports dynamic segments).
     * @param array{0: class-string, 1: string} $handler Controller class and method handler.
     * @return void
     */
    public function add(ERequestMethods $method, string $uriPattern, array $handler): void;

    /**
     * Dispatch the current HTTP request to the appropriate route handler.
     *
     * @return void
     */
    public function dispatch(): void;
}