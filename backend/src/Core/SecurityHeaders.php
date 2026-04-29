<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function apply(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Control referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Prevent XSS via strict CSP
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self'; style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data:; font-src 'self'; " .
               "connect-src 'self'; frame-ancestors 'none'");

        // Remove PHP version disclosure
        header_remove('X-Powered-By');
    }
}
