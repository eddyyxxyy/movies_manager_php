<?php

declare(strict_types=1);

namespace App\Core\CSRF;

use App\Contracts\CsrfTokenInterface;
use App\Contracts\SessionInterface;

/**
 * Manages and validates CSRF tokens.
 */
class CsrfToken implements CsrfTokenInterface
{
    private const string TOKEN_KEY = '_csrf_token';

    /**
     * @param SessionInterface $session The session management service.
     */
    public function __construct(private SessionInterface $session)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function generate(): string
    {
        if (!$this->session->has(self::TOKEN_KEY)) {
            $this->session->set(self::TOKEN_KEY, bin2hex(random_bytes(32))); // Generates 64 hexadecimal characters
        }
        return $this->session->get(self::TOKEN_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): ?string
    {
        return $this->session->get(self::TOKEN_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $token): bool
    {
        return $this->session->has(self::TOKEN_KEY) && hash_equals($this->session->get(self::TOKEN_KEY), $token);
    }
}