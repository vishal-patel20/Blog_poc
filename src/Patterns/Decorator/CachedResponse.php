<?php
namespace App\Patterns\Decorator;

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