<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Contracts\MiddlewareInterface;
use App\Contracts\SessionInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * Middleware to check if the user is authenticated.
 * Redirects to the login page if the user is not logged in.
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @param SessionInterface $session The session management service.
     */
    public function __construct(private SessionInterface $session)
    {
    }

    /**
     * Processes the request to check for user authentication.
     *
     * @param Request $request The HTTP request.
     * @param callable $next The next middleware or the route handler.
     * @return Response The HTTP response (either a redirect to login or the response from the next handler).
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->session->has('user_id') || empty($this->session->get('user_id'))) {
            // TODO: add flash messages
            return Response::redirect('/login');
        }

        return $next($request);
    }
}