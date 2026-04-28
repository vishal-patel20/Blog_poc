<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Atomic File-based Rate Limiter.
 *
 * Fix #2: Uses exclusive file locking (flock LOCK_EX) to perform
 * read-modify-write atomically, eliminating the TOCTOU race condition.
 * Fix #8: Single flock covers both read and write in one critical section.
 */
class RateLimiter
{
    private const FILE_PATH = __DIR__ . '/../../database/rate_limits.json';
    private const LIMIT     = 60; // Max requests per window
    private const WINDOW    = 60; // Window duration in seconds

    public static function check(string $ip): void
    {
        // Ensure directory exists
        $dir = dirname(self::FILE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Open file for reading + writing, create if not exists
        $fh = fopen(self::FILE_PATH, 'c+');
        if ($fh === false) {
            // Vulnerability Fix #8: Fail CLOSED — if rate-limit state is unavailable,
            // deny the request with 503 rather than silently bypassing all rate limiting.
            Response::error('Service temporarily unavailable. Please try again later.', 503);
        }

        // Acquire EXCLUSIVE lock — only one process enters this block at a time
        // This eliminates the TOCTOU race condition between read and write
        flock($fh, LOCK_EX);

        try {
            $content = stream_get_contents($fh);
            $data    = ($content !== false && $content !== '')
                ? (json_decode($content, true) ?? [])
                : [];

            $now = time();

            // Clean up expired entries
            $data = array_filter(
                $data,
                static fn(array $entry): bool => $entry['expires_at'] >= $now
            );

            if (!isset($data[$ip])) {
                $data[$ip] = ['hits' => 1, 'expires_at' => $now + self::WINDOW];
            } else {
                $data[$ip]['hits']++;

                if ($data[$ip]['hits'] > self::LIMIT) {
                    // Write updated count before responding
                    rewind($fh);
                    ftruncate($fh, 0);
                    fwrite($fh, json_encode(array_values($data) !== $data ? $data : $data));
                    flock($fh, LOCK_UN);
                    fclose($fh);

                    Response::error('Too Many Requests. Slow down and try again later.', 429);
                }
            }

            // Write updated data atomically
            rewind($fh);
            ftruncate($fh, 0);
            fwrite($fh, (string) json_encode($data));
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
