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

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        exit;
    }
}
