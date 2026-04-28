<?php

declare(strict_types=1);
namespace App\Solid\Isp;

interface WritableInterface {
    public function write(array $data): bool;
}