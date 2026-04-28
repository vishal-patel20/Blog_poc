<?php

declare(strict_types=1);
namespace App\Solid\Dip;

class PDOPostRepository implements PostRepositoryInterface {
    public function getPosts(): array {
        return ['DB Post 1', 'DB Post 2'];
    }
}