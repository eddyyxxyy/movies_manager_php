<?php
/**
 * PHP's built-in web server router script.
 * This script is used when running `php -S localhost:8000 public/router.php`.
 * It serves static files directly if they exist, otherwise it passes the request
 * to the main `index.php` for dynamic routing by the framework.
 */

// Define the root directory of your public assets.
$publicRoot = __DIR__;

// Get the requested URI path. Remove query string if present.
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Check if the requested URI maps to an existing static file within the public root.
// For example, if request is /css/style.css, it checks public/css/style.css.
if ($uri !== '/' && file_exists($publicRoot . $uri)) {
    // If the file exists, serve it directly and exit.
    // This is crucial for performance and correct handling of static assets.
    return false; // Tells the PHP built-in server to serve the file.
}

// Include index.php to process the non static request.
require_once $publicRoot . '/index.php';