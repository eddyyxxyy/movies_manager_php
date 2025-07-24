<?php

declare(strict_types=1);

use App\Core\Kernel;
use App\Core\Config;
use App\Core\Container;
use App\Core\Http\ExceptionHandler;

require_once __DIR__ . '/../vendor/autoload.php';

if (!is_dir(Config::CACHE_DIR)) {
    mkdir(Config::CACHE_DIR, 0775, true);
}

$container = new Container();

// Bind optional definitions (ex: database, services, etc)
// $container->bind(...);

// Creates the router with DI container
$routerFactory = require Config::APP_DIR . 'routes.php';
$router = $routerFactory($container);

$exceptionHandler = $container->resolve(ExceptionHandler::class);

$kernel = new Kernel($container, $router, $exceptionHandler);

return $kernel;
