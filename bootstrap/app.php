<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Kernel;
use App\Core\Routing\Router;
use App\Providers\RouteServiceProvider;
use App\Core\Http\ExceptionHandler;

require_once __DIR__ . '/../vendor/autoload.php';

// Ensure cache directory exists
if (!is_dir(Config::CACHE_DIR)) {
    mkdir(Config::CACHE_DIR, 0775, true);
}

$container = new Container();

// Bind interfaces or special bindings here, if needed
// e.g. $container->bind(SomeInterface::class, SomeImplementation::class);

// Resolve the Router through a RouteServiceProvider to register routes
$routeServiceProvider = $container->resolve(RouteServiceProvider::class);
$router = $container->resolve(Router::class);

// Register routes on the router
$routeServiceProvider->register($router);

// Resolve ExceptionHandler via autowiring
$exceptionHandler = $container->resolve(ExceptionHandler::class);

// Instantiate and return the Kernel
return new Kernel($container, $router, $exceptionHandler);
