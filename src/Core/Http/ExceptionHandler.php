<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Config;
use Throwable;

/**
 * The application's main safety net.
 * Catches all uncaught errors and decides what to show.
 */
class ExceptionHandler
{
    public function handle(Throwable $e): void
    {
        http_response_code(500); // Assume a server error

        if (Config::APP_DEBUG) {
            // In debug mode, show everything.
            $this->showDetailedError($e);
        } else {
            // In production, log the real error and show a generic page.
            $this->logError($e);
            $this->showGenericErrorPage();
        }
    }

    private function showDetailedError(Throwable $e): void
    {
        // TODO: a view here
        echo "<h1>500 Internal Server Error</h1>";
        echo "<h3>" . get_class($e) . "</h3>";
        echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
        echo "<hr><h3>Stack Trace:</h3><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

    private function logError(Throwable $e): void
    {
        // This sends the error to your PHP error log (e.g., /var/log/nginx/error.log)
        error_log(
            "Uncaught " . get_class($e) . ": " . $e->getMessage() .
            " in " . $e->getFile() . ":" . $e->getLine() . "\n" .
            $e->getTraceAsString()
        );
    }

    private function showGenericErrorPage(): void
    {
        // TODO: a view here
        echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
        echo "<h1>Oops! Something went wrong.</h1>";
        echo "<p>We are sorry, but the application encountered an error. Please try again later.</p>";
        echo "</body></html>";
    }
}