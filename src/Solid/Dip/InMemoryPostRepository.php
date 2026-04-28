<?php
namespace App\Solid\Dip;

class InMemoryPostRepository implements PostRepositoryInterface {
    public function getPosts(): array {
        return ['Memory Post 1', 'Memory Post 2'];
    }
}