<?php
namespace App\Solid\Dip;

interface PostRepositoryInterface {
    public function getPosts(): array;
}