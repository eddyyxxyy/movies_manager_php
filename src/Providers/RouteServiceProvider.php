<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Routing\Router;
use App\Enums\ERequestMethods;
use App\Http\Controllers\HomeController;

/**
 * Registers application routes to the Router.
 */
final class RouteServiceProvider
{
    /**
     * Register all routes to the router instance.
     *
     * @param Router $router The router instance to register routes on
     * @return void
     */
    public function register(Router $router): void
    {
        // Routes
        $router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);
    }
}
