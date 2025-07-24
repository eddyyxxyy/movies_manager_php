<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Contracts\ExceptionHandlerInterface;
use App\Core\AppConfig;
use Throwable;

/**
 * The application's main safety net.
 * Catches all uncaught errors and decides what to show.
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * Create a new ExceptionHandler instance.
     *
     * @param AppConfig $config The application configuration.
     */
    public function __construct(private AppConfig $config)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Throwable $e): void
    {
        http_response_code(500); // Assume a server error

        if ($this->config->isDebug()) {
            $this->showDetailedError($e);
        } else {
            $this->logError($e);
            $this->showGenericErrorPage();
        }
    }

    /**
     * Renders a detailed error page for development.
     */
    private function showDetailedError(Throwable $e): void
    {
        // TODO: this should render a view.
        echo "<h1>500 Internal Server Error</h1>";
        echo "<h3>" . get_class($e) . "</h3>";
        echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
        echo "<hr><h3>Stack Trace:</h3><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

    /**
     * Logs the error for production environments.
     */
    private function logError(Throwable $e): void
    {
        error_log(
            sprintf(
                "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            )
        );
    }

    /**
     * Renders a generic error page for production.
     */
    private function showGenericErrorPage(): void
    {
        // TODO: this should render a view.
        echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
        echo "<h1>Oops! Something went wrong.</h1>";
        echo "<p>We are sorry, but the application encountered an error. Please try again later.</p>";
        echo "</body></html>";
    }
}