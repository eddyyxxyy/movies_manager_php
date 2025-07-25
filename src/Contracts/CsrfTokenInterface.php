<?php

declare(strict_types=1);

namespace App\Contracts;

interface CsrfTokenInterface
{
    /**
     * Generates and stores a new CSRF token in the session, if one does not exist.
     *
     * @return string The CSRF token.
     */
    public function generate(): string;

    /**
     * Gets the current CSRF token from the session.
     *
     * @return string|null The token or null if none exists.
     */
    public function getToken(): ?string;

    /**
     * Validates a provided CSRF token against the token stored in the session.
     * Uses a constant-time comparison to prevent timing attacks.
     *
     * @param string $token The token received from the request.
     * @return bool True if the token is valid, false otherwise.
     */
    public function validate(string $token): bool;
}