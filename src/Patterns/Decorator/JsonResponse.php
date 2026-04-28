<?php
namespace App\Patterns\Decorator;

final class JsonResponse implements ResponseInterface {
    public function __construct(private array $data) {}

    public function getHeaders(): array {
        return ['Content-Type' => 'application/json'];
    }

    public function getBody(): string {
        return json_encode($this->data);
    }
}