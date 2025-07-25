<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\AppConfig;

/**
 * Service provider for session management.
 * This provider handles session configuration and initialization, ensuring
 * secure defaults are set before the session starts.
 */
final class SessionServiceProvider
{
    /**
     * The AppConfig instance, injected by the Container
     */
    public function __construct(private AppConfig $config)
    {
    }

    /**
     * Registers session-related services and configures PHP's session settings.
     * This method is called during the application's bootstrap process.
     * 
     * ** Use it before session_start() **
     *
     * @return void
     */
    public function register(): void
    {
        // No session on url
        ini_set('session.use_only_cookies', '1');

        // Prevent js access to session cookie
        ini_set('session.cookie_httponly', '1');

        // Only https on prod
        if ($this->config->get('env') === 'production' && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            ini_set('session.cookie_secure', '1');
        } else {
            // For dev env
            ini_set('session.cookie_secure', '0');
        }

        // Cookie CORS
        if (PHP_VERSION_ID >= 70300) {
            ini_set('session.cookie_samesite', 'Lax');
        }

        // Prevent Session Fixation
        ini_set('session.use_strict_mode', '1');

        // Sets the maximum lifetime for session data
        $sessionLifetime = 3600; // 1 hour -> 3600 sec
        ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
        ini_set('session.cookie_lifetime', (string) $sessionLifetime);

        // Start or resume the PHP session.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}