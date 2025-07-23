<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Container;

if (!is_dir(Config::CACHE_DIR)) {
    mkdir(Config::CACHE_DIR, 0775, true);
}

$container = new Container();

// Optional bindings here
// $container->bind(...);

// Load router from route definitions
$routerFactory = require __DIR__ . '/../src/routes.php';
$router = $routerFactory($container);

return [$container, $router];
