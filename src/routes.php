<?php

declare(strict_types=1);

use App\Core\Container;
use App\Core\Routing\Router;
use App\Enums\ERequestMethods;
use App\Controllers\HomeController;
use App\Core\Routing\RouteExecutor;

return function (Container $container): Router {
    $executor = $container->resolve(RouteExecutor::class);
    $router = new Router($executor);

    // Routes
    $router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);

    return $router;
};
