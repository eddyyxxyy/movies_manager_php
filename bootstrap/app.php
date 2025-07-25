<?php

declare(strict_types=1);

use App\Contracts\ContainerInterface;
use App\Contracts\CsrfTokenInterface;
use App\Contracts\ExceptionHandlerInterface;
use App\Contracts\RouterInterface;
use App\Contracts\SessionInterface;
use App\Contracts\ViewInterface;
use App\Core\AppConfig;
use App\Core\Container;
use App\Core\CSRF\CsrfToken;
use App\Core\Database\DatabaseManager;
use App\Core\Files\Uploader;
use App\Core\Http\ExceptionHandler;
use App\Core\Kernel;
use App\Core\Routing\Router;
use App\Core\Session\Session;
use App\Core\View\View;
use App\DAO\UserDAO;
use App\Services\UserService;
use App\Providers\RouteServiceProvider;
use App\Providers\SessionServiceProvider;

// 1. Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load environment variables
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// 3. Load application and database configuration
$appConfigValues = require __DIR__ . '/../config/app.php';
$dbConfigValues = require __DIR__ . '/../config/database.php';

// 3.1 Merge all config files
$appConfigValues = array_merge($appConfigValues, ['database' => $dbConfigValues]);

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

// Bind Session manager as singleton
$container->singleton(SessionInterface::class, Session::class);

// Bind CsrfTokenInterface as singleton.
$container->singleton(CsrfTokenInterface::class, CsrfToken::class);

// Bind DatabaseManager and actual connection as a singleton
$container->singleton(DatabaseManager::class, DatabaseManager::class);
$container->singleton(PDO::class, fn(ContainerInterface $c) => $c->resolve(DatabaseManager::class)->getConnection());

// Bind File Uploader as a singleton
$container->singleton(Uploader::class, fn(ContainerInterface $c) => new Uploader($c->resolve(AppConfig::class)->get('upload_dir')));

// Bind ExceptionHandler and Router as singletons
$container->singleton(ExceptionHandlerInterface::class, ExceptionHandler::class);
$container->singleton(RouterInterface::class, Router::class);

// Bind Data Access Objects (DAOs) as singletons
$container->singleton(UserDAO::class, UserDAO::class);

// Bind Business Services as singletons
$container->singleton(UserService::class, UserService::class);

// Bind View as non shared component of the application
$container->bind(ViewInterface::class, View::class);


/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

$sessionServiceProvider = $container->resolve(SessionServiceProvider::class);
$sessionServiceProvider->register();

$routeServiceProvider = $container->resolve(RouteServiceProvider::class);
$routeServiceProvider->register($container->resolve(RouterInterface::class));

/*
|--------------------------------------------------------------------------
| Create and Return The Kernel
|--------------------------------------------------------------------------
*/

return $container->resolve(Kernel::class);