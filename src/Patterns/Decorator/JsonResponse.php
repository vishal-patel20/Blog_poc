<?php

declare(strict_types=1);
namespace App\Patterns\Decorator;

final class JsonResponse implements ResponseInterface {
    public function __construct(private array $data) {}

    public function getHeaders(): array {
        return ['Content-Type' => 'application/json'];
    }

    public function getBody(): string {
        // Security Fix #21: Use JSON_HEX_* flags to escape HTML special characters
        // (<, >, &, ', ") — consistent with the main Response class standard.
        return (string) json_encode(
            $this->data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }
}