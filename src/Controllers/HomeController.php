<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Controller for the home page.
 */
class HomeController
{
    /**
     * Handles requests to the root path ('/').
     *
     * @return void
     */
    public function index(): void
    {
        echo '<h1>Welcome to Movies Manager!</h1>';
    }

    /**
     * Example route with a dynamic path parameter.
     *
     * @param string $name
     * @return void
     */
    public function greet(string $name): void
    {
        echo "<h1>Hello, " . htmlspecialchars($name) . "!</h1>";
    }
}
