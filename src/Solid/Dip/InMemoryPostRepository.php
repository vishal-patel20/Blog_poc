<?php

declare(strict_types=1);
namespace App\Solid\Dip;

class InMemoryPostRepository implements PostRepositoryInterface {
    public function getPosts(): array {
        return ['Memory Post 1', 'Memory Post 2'];
    }
}