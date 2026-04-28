<?php

declare(strict_types=1);
namespace App\Solid\Dip;

interface PostRepositoryInterface {
    public function getPosts(): array;
}