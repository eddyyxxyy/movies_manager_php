<?php

declare(strict_types=1);

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

$router = require_once __DIR__ . '/../src/routes.php';
$router->dispatch();
