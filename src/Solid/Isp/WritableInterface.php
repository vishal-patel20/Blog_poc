<?php
namespace App\Solid\Isp;

interface WritableInterface {
    public function write(array $data): bool;
}