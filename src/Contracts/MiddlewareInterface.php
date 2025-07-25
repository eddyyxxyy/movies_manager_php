<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Core\Http\Request;
use App\Core\Http\Response;

interface MiddlewareInterface
{
    /**
     * Processes an HTTP request and/or response.
     *
     * @param Request $request The HTTP request.
     * @param callable $next The next middleware in the pipeline or the route handler.
     * It's expected to accept a Request and return a Response.
     * @return Response The HTTP response.
     */
    public function process(Request $request, callable $next): Response;
}