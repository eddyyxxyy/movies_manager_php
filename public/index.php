<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Routing\RouteExecutor;

// Ensure cache directory exists
if (!is_dir(Config::CACHE_DIR)) {
    mkdir(Config::CACHE_DIR, 0775, true);
}

$routerFactory = require __DIR__ . '/../src/routes.php';
$executor = new RouteExecutor();
$router = $routerFactory($executor);
$router->dispatch();
