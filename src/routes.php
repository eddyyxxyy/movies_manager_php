<?php

declare(strict_types=1);

use App\Core\Routing\Router;
use App\Enums\ERequestMethods;
use App\Controllers\HomeController;

$router = new Router();
$router->enableDebug(); // Turn off in production

$router->add(ERequestMethods::GET, '/', [HomeController::class, 'index']);

return $router;
