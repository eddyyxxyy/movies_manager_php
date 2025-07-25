<?php

declare(strict_types=1);

namespace App\Core\Session;

use App\Contracts\SessionInterface;

/**
 * Manages PHP session data.
 */
class Session implements SessionInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(): void
    {
        $_SESSION = []; // Clear all session data in the superglobal
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy(); // Destroys session data on the server
    }
}