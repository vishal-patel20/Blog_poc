<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

abstract class DatabaseFactory {
    abstract public function createConnection(): ConnectionInterface;
}