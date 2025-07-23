<?php

declare(strict_types=1);

use App\Core\Http\ExceptionHandler;

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    [$container, $router] = require __DIR__ . '/../bootstrap/app.php';
    $router->dispatch();
} catch (Throwable $e) {
    (new ExceptionHandler())->handle($e);
}
