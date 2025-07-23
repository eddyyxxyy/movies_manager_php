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
    protected string $content;
    protected int $statusCode;
    protected array $headers;

    /**
     * @param string $content Body content of the response (HTML, JSON, text)
     * @param int $statusCode HTTP status code (200, 404, 500, etc)
     * @param array<string, string> $headers HTTP headers (Content-Type, Cache-Control, etc)
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = ['Content-Type' => 'text/html'])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Sends HTTP headers and outputs the response content.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    /**
     * Factory method for JSON responses.
     *
     * @param array $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(json_encode($data, JSON_THROW_ON_ERROR), $statusCode, ['Content-Type' => 'application/json']);
    }

    /**
     * Factory method for HTML responses.
     *
     * @param string $html HTML content
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, ['Content-Type' => 'text/html']);
    }
}
