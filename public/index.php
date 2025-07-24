<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$kernel = require __DIR__ . '/../bootstrap/app.php';
$kernel->run();
