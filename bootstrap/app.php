<?php

declare(strict_types=1);

use App\Contracts\ContainerInterface;
use App\Contracts\ExceptionHandlerInterface;
use App\Contracts\RouterInterface;
use App\Contracts\ViewInterface;
use App\Core\AppConfig;
use App\Core\Container;
use App\Core\Http\ExceptionHandler;
use App\Core\Kernel;
use App\Core\Routing\Router;
use App\Core\View\View;
use App\Providers\RouteServiceProvider;

// 1. Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load environment variables
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// 3. Load application configuration
$appConfigValues = require __DIR__ . '/../config/app.php';

// 4. Ensure cache directory exists
if (!is_dir($appConfigValues['cache_dir'])) {
    mkdir($appConfigValues['cache_dir'], 0775, true);
}

// 5. Create the service container
$container = new Container();

/*
|--------------------------------------------------------------------------
| Bind Core Application Components
|--------------------------------------------------------------------------
*/

// Bind the container itself to its interface for self-resolution
$container->singleton(ContainerInterface::class, $container);

// Bind the AppConfig as a singleton
$container->singleton(AppConfig::class, new AppConfig($appConfigValues));

// Bind core interfaces to their concrete implementations
$container->singleton(ExceptionHandlerInterface::class, ExceptionHandler::class);
$container->singleton(RouterInterface::class, Router::class);
$container->bind(ViewInterface::class, View::class);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

$routeServiceProvider = $container->resolve(RouteServiceProvider::class);
$routeServiceProvider->register($container->resolve(RouterInterface::class));

/*
|--------------------------------------------------------------------------
| Create and Return The Kernel
|--------------------------------------------------------------------------
*/

return $container->resolve(Kernel::class);