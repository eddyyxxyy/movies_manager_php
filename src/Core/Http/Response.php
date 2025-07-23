<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Class Response
 *
 * Encapsulates an HTTP response.
 * Handles status codes, headers, and body content.
 */
class Response
{
    /**
     * The body content of the response.
     *
     * @var string
     */
    protected string $content;

    /**
     * The HTTP status code.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * Associative array of HTTP headers.
     *
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * Constructs a new Response object.
     *
     * @param string $content Body content of the response (HTML, JSON, plain text, etc)
     * @param int $statusCode HTTP status code (e.g., 200, 404, 500)
     * @param array<string, string> $headers Associative array of HTTP headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = ['Content-Type' => 'text/html'])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Sends HTTP headers and outputs the response body.
     *
     * @return void
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
     *
     * @param array|\JsonSerializable $data Data to be JSON-encoded
     * @param int $statusCode HTTP status code
     * @return self JSON response object
     *
     * @throws \JsonException If encoding fails
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR), $statusCode, ['Content-Type' => 'application/json']);
    }

    /**
     * Creates a new HTML response.
     *
     * @param string $html HTML content
     * @param int $statusCode HTTP status code
     * @return self HTML response object
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, ['Content-Type' => 'text/html']);
    }

    /**
     * Returns a copy of the response with an added or replaced header.
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return self New response instance with the updated header
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Creates a redirect response.
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (usually 302 for temporary redirect)
     * @return self Redirect response object
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }
}
