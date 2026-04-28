<?php

declare(strict_types=1);
namespace App\Patterns\Decorator;

/**
 * CachedResponse Decorator — adds Cache-Control: max-age=3600.
 *
 * ⚠️  SECURITY WARNING (Fix #19):
 * This decorator sets `Cache-Control: max-age=3600`, which CONFLICTS with the
 * `Cache-Control: no-store, max-age=0` security header applied by
 * Response::applySecurityHeaders() on all API endpoints.
 *
 * DO NOT wrap authenticated or sensitive API responses with this decorator.
 * It is intended ONLY for public, non-sensitive static content responses.
 * Applying it to API responses will cause sensitive data to be cached in
 * browser caches and shared proxy caches for up to 1 hour.
 */
final class CachedResponse implements ResponseInterface {
    public function __construct(private ResponseInterface $wrapped) {}

    public function getHeaders(): array {
        $headers = $this->wrapped->getHeaders();
        $headers['Cache-Control'] = 'max-age=3600';
        return $headers;
    }

    public function getBody(): string {
        return $this->wrapped->getBody();
    }
}