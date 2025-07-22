<?php

declare(strict_types=1);

use App\Core\Router;
use App\Enums\ERequestMethods;
use App\Controllers\HomeController;

$router = new Router();
$router->enableDebug(); // Turn off in production

$router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);
$router->add(ERequestMethods::GET, '/greet/{name}', [HomeController::class, 'greet']);

return $router;
