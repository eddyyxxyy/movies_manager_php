<?php

declare(strict_types=1);

namespace App\Core\Http;

use JsonSerializable;

/**
 * Encapsulates an HTTP response.
 * Handles status codes, headers, and body content.
 */
class Response
{
    /**
     * @param string $content Body content of the response.
     * @param int $statusCode HTTP status code.
     * @param array<string, string> $headers Associative array of HTTP headers.
     */
    public function __construct(
        protected string $content = '',
        protected int $statusCode = 200,
        protected array $headers = ['Content-Type' => 'text/html']
    ) {
    }

    /**
     * Sends HTTP headers and outputs the response body.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}", true);
        }

        echo $this->content;
    }

    /**
     * Creates a new JSON response.
     */
    public static function json(array|JsonSerializable $data, int $statusCode = 200): self
    {
        return new self(
            json_encode($data, JSON_THROW_ON_ERROR),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Creates a new HTML response.
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, ['Content-Type' => 'text/html']);
    }

    /**
     * Returns a copy of the response with an added or replaced header.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Creates a redirect response.
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }
}