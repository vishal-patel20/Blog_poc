<?php
namespace App\Solid\Isp;

interface ReadableInterface {
    public function read(int $id): array;
}