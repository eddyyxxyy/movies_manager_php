<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Entry point for the application.
 * Loads the Kernel from bootstrap/app.php and runs it.
 */

// 1. Require the application bootstrap file
$kernel = require __DIR__ . '/../bootstrap/app.php';

// 2. Run the application
$kernel->run();
