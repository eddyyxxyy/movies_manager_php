<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\RouterInterface;
use App\Enums\ERequestMethods;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\CsrfProtectionMiddleware;

/**
 * Registers application routes with the Router.
 */
final class RouteServiceProvider
{
    /**
     * Register all routes on the router instance.
     *
     * @param RouterInterface $router The router instance to register routes on.
     * @return void
     */
    public function register(RouterInterface $router): void
    {
        $router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);

        // Auth routes
        // GET /login
        $router->add(ERequestMethods::GET, '/login', [AuthController::class, 'showLoginForm']);
        // POST /login requires CSRF protection
        $router->add(ERequestMethods::POST, '/login', [AuthController::class, 'login', [CsrfProtectionMiddleware::class]]);
        // GET /logout requires authentication to ensure only logged-in users can log out
        $router->add(ERequestMethods::GET, '/logout', [AuthController::class, 'logout', [AuthMiddleware::class]]);


        // After all routes are added, cache them for production.
        if (method_exists($router, 'cacheRoutes')) {
            $router->cacheRoutes();
        }
    }
}