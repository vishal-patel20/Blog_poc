<?php
namespace App\Patterns\Decorator;

final class GzippedResponse implements ResponseInterface {
    public function __construct(private ResponseInterface $wrapped) {}

    public function getHeaders(): array {
        $headers = $this->wrapped->getHeaders();
        $headers['Content-Encoding'] = 'gzip';
        return $headers;
    }

    public function getBody(): string {
        return gzencode($this->wrapped->getBody(), 9);
    }
}