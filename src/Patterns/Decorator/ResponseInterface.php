<?php
namespace App\Patterns\Decorator;

/**
 * Decorator Pattern
 * Problem it solves: Need to add behaviors (caching, gzipping) to HTTP responses dynamically.
 * Why chosen: Allows stacking decorators (e.g., Gzip -> Cache -> JSON) without creating subclasses for every combination.
 * What breaks if removed: We'd have class explosion (e.g., CachedGzippedJsonResponse).
 */
interface ResponseInterface {
    public function getHeaders(): array;
    public function getBody(): string;
}