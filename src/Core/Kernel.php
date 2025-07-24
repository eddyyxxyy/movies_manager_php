<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ExceptionHandlerInterface;
use App\Contracts\RouterInterface;
use Throwable;

/**
 * The Application Kernel.
 *
 * Handles the full application lifecycle: receives a request,
 * sends it through the router, and returns a response.
 */
final class Kernel
{
    /**
     * Create a new Kernel instance.
     *
     * @param RouterInterface $router The application router.
     * @param ExceptionHandlerInterface $exceptionHandler The application exception handler.
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ExceptionHandlerInterface $exceptionHandler,
    ) {
    }

    /**
     * Run the application.
     *
     * Dispatches the router and handles any uncaught exceptions.
     * @return void
     */
    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Throwable $e) {
            $this->exceptionHandler->handle($e);
        }
    }
}