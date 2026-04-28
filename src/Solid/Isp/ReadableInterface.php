<?php

declare(strict_types=1);
namespace App\Solid\Isp;

interface ReadableInterface {
    public function read(int $id): array;
}