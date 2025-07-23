<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Container;

// Ensure cache dir
if (!is_dir(Config::CACHE_DIR)) {
    mkdir(Config::CACHE_DIR, 0775, true);
}

$container = new Container();

// Manual Binds
// $container->bind(MovieRepositoryInterface::class, fn() => new MovieRepository());

/** @var callable(Container): \App\Core\Routing\Router $routerFactory */
$routerFactory = require __DIR__ . '/../src/routes.php';

$router = $routerFactory($container);
$router->dispatch();
