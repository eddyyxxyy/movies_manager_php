<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Represents an HTTP request.
 *
 * Provides a clean, object-oriented interface to access request data
 * (query params, post data, method, URI, headers, etc.).
 */
class Request
{
    /**
     * Returns the HTTP method (GET, POST, etc.).
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Returns the request URI path (without query string).
     */
    public function getUri(): string
    {
        return strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    }

    /**
     * Retrieves a request parameter from POST or GET.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Returns all input parameters from both GET and POST.
     */
    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Returns whether the request was made via AJAX.
     */
    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
}