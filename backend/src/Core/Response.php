<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JSON Response builder.
 *
 * Sends a JSON-encoded response with the appropriate HTTP status code
 * and Content-Type header.
 */
class Response
{
    /**
     * Send a successful JSON response.
     *
     * @param array<string, mixed>|list<mixed> $data
     */
    public static function json(array $data, int $status = 200): void
    {
        self::send($data, $status);
    }

    /**
     * Send a 201 Created response.
     *
     * @param array<string, mixed> $data
     */
    public static function created(array $data): void
    {
        self::send($data, 201);
    }

    /**
     * Send a 204 No Content response.
     */
    public static function noContent(): void
    {
        http_response_code(204);
        header('Content-Type: application/json');
        self::applySecurityHeaders();
        exit;
    }

    /**
     * Send an error JSON response.
     *
     * @param string|array<string, mixed> $message
     */
    public static function error(string|array $message, int $status = 400): void
    {
        $body = is_array($message)
            ? ['error' => $message]
            : ['error' => $message];

        self::send($body, $status);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Encode and output JSON, then halt execution.
     *
     * @param array<string, mixed>|list<mixed> $data
     */
    private static function send(array $data, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        self::applySecurityHeaders();

        // Vulnerability Fix #5: Include JSON_HEX_* flags to escape HTML special
        // characters (<, >, &, ', ") as Unicode escape sequences. This prevents
        // XSS if a consumer embeds this JSON directly inside an HTML <script> block.
        echo json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        exit;
    }

    /**
     * Apply OWASP-recommended API security headers.
     *
     * Vulnerability Fix #6: CORS restricted to CORS_ALLOWED_ORIGIN env var (no wildcard).
     * Vulnerability Fix #7: Content-Security-Policy header added.
     */
    private static function applySecurityHeaders(): void
    {
        // Vulnerability Fix #16: Emit a unique X-Request-ID on every response.
        // Echoes back the caller's ID if provided, or generates one.
        // This allows correlation of requests across client logs and server error_log().
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
        header("X-Request-ID: {$requestId}");

        // Prevent MIME-type sniffing
        header('X-Content-Type-Options: nosniff');

        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Force HTTPS for 1 year (only effective over TLS)
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // No caching of sensitive API responses
        header('Cache-Control: no-store, max-age=0');

        // Prevent referrer leakage
        header('Referrer-Policy: no-referrer');

        // Restrict access to sensitive browser APIs
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Vulnerability Fix #7: CSP — default-src 'none' is correct for a pure JSON API;
        // no scripts, images, or frames are ever served from this origin.
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

        // Vulnerability Fix #6: CORS — NO wildcard. Read allowed origin from environment.
        // Set CORS_ALLOWED_ORIGIN=https://yourfrontend.com in your .env / server config.
        // If not set, no ACAO header is emitted (most restrictive — same-origin only).
        $allowedOrigin = $_ENV['CORS_ALLOWED_ORIGIN'] ?? '';
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($allowedOrigin !== '' && $requestOrigin === $allowedOrigin) {
            header("Access-Control-Allow-Origin: {$requestOrigin}");
            header('Vary: Origin');
        } elseif ($allowedOrigin !== '') {
            // Non-matching origin — send the configured allowed origin so the
            // browser knows the request is rejected by CORS policy.
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
            header('Vary: Origin');
        }
        // else: CORS_ALLOWED_ORIGIN not configured — no header sent (deny all cross-origin)

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }
}
