<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Enums\ERequestMethods;
use App\Controllers\HomeController;
use App\Core\Routing\RouteExecutor;

return function (RouteExecutor $executor): Router {
    $router = new Router($executor);
    $router->enableDebug();
    $router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);
    return $router;
};
