<?php

declare(strict_types=1);

namespace App\Contracts;

use Throwable;

/**
 * Interface for the application's main exception handler.
 *
 * Defines the contract for catching all uncaught throwables and
 * formatting an appropriate response.
 */
interface ExceptionHandlerInterface
{
    /**
     * Handle an uncaught throwable.
     *
     * @param Throwable $e The caught throwable.
     * @return void
     */
    public function handle(Throwable $e): void;
}