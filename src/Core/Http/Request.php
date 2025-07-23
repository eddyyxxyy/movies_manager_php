<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Class Request
 *
 * Represents an HTTP request received by the server.
 * Provides a clean, object-oriented interface to access request data
 * (query params, post data, method, URI, headers, etc).
 */
class Request
{
    /**
     * Returns the HTTP method (GET, POST, PUT, DELETE, etc).
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Returns the request URI path (without query string).
     * Example: /users/123
     */
    public function getUri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    /**
     * Retrieves a request parameter from POST or GET.
     * POST has precedence over GET.
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter not found
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Returns all input parameters from both GET and POST.
     * POST parameters overwrite GET parameters on conflicts.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Returns whether the request was made via AJAX.
     *
     * Useful for returning different content or headers in AJAX calls.
     */
    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
