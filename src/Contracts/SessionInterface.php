<?php

declare(strict_types=1);

namespace App\Contracts;

interface SessionInterface
{
    /**
     * Retrieves a value from the session by key.
     *
     * @param string $key The key to retrieve.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The session value or the default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Sets a value in the session.
     *
     * @param string $key The key.
     * @param mixed $value The value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Removes a value from the session by key.
     *
     * @param string $key The key to remove.
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Checks if a key exists in the session.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Destroys the current session data on both the server and client (cookie).
     *
     * @return void
     */
    public function destroy(): void;
}