<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\RouterInterface;
use App\Enums\ERequestMethods;
use App\Http\Controllers\HomeController;

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
        $router->add(ERequestMethods::GET, '/users/{id}', [HomeController::class, 'showUser']);

        // After all routes are added, cache them for production.
        if (method_exists($router, 'cacheRoutes')) {
            $router->cacheRoutes();
        }
    }
}