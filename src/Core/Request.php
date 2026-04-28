<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request wrapper.
 *
 * Wraps $_SERVER superglobal and reads the raw request body from php://input.
 */
class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri         = $this->parseUri();
        $this->queryParams = $_GET;
        $this->headers     = $this->parseHeaders();
        $this->body        = $this->parseBody();
    }

    /**
     * Return the HTTP method (GET, POST, PUT, PATCH, DELETE).
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return the URI path without the query string.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Return a query parameter value by key, or a default.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Return the full query params array.
     *
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * Return a body parameter value by key, or a default.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Return the full decoded request body as an associative array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Return a request header by name (case-insensitive), or a default.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);

        return $this->headers[$key] ?? $default;
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Strip the query string from REQUEST_URI and URL-decode.
     */
    private function parseUri(): string
    {
        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path   = parse_url($rawUri, PHP_URL_PATH);

        return rawurldecode((string) $path);
    }

    /**
     * Decode the raw request body as JSON. Falls back to an empty array.
     *
     * Vulnerability Fix #4: Body is capped at 2 MB (2,097,152 bytes) to prevent
     * DoS attacks where a caller sends a huge payload to exhaust PHP worker memory.
     *
     * @return array<string, mixed>
     */
    private function parseBody(): array
    {
        $maxBytes = 2 * 1024 * 1024; // 2 MB hard cap
        $handle   = fopen('php://input', 'r');

        if ($handle === false) {
            return [];
        }

        $raw = stream_get_contents($handle, $maxBytes);
        fclose($handle);

        if ($raw === false || $raw === '') {
            return [];
        }

        // Vulnerability Fix #12: Limit JSON nesting depth to 16.
        // Default depth is 512 — a deeply nested 2 MB payload can exhaust
        // hundreds of MB of PHP worker memory within the allowed body size.
        $decoded = json_decode($raw, true, 16);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Collect HTTP headers from $_SERVER into a normalised array.
     *
     * @return array<string, string>
     */
    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        return $headers;
    }
}
