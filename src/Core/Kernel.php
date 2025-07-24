<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Http\ExceptionHandler;
use App\Core\Routing\Router;
use Throwable;

/**
 * The Kernel handles the full application lifecycle.
 */
final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Throwable $e) {
            $this->exceptionHandler->handle($e);
        }
    }
}
