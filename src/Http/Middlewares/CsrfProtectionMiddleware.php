<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Contracts\MiddlewareInterface;
use App\Contracts\CsrfTokenInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Middleware for CSRF protection.
 * Validates the CSRF token for state-modifying requests (POST, PUT, PATCH, DELETE).
 */
class CsrfProtectionMiddleware implements MiddlewareInterface
{
    /**
     * @param CsrfTokenInterface $csrfToken The CSRF token management service.
     */
    public function __construct(private CsrfTokenInterface $csrfToken)
    {
    }

    /**
     * Processes the request to validate the CSRF token.
     *
     * @param Request $request The HTTP request.
     * @param callable $next The next middleware or the route handler.
     * @return Response The HTTP response (either a 419 error or the response from the next handler).
     */
    public function process(Request $request, callable $next): Response
    {
        // Validate only for methods that modify the state (POST, PUT, PATCH, DELETE)
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->input('_csrf_token');
            if (!$this->csrfToken->validate($token ?? '')) {
                // TODO: add flash messages
                return Response::html("<h1>419 Page Expired (CSRF Token Mismatch)</h1><p>The page has expired due to inactivity or invalid token. Please refresh and try again.</p>", 419);
            }
        }

        return $next($request);
    }
}