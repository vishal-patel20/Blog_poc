<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

class MySQLFactory extends DatabaseFactory {
    public function createConnection(): ConnectionInterface {
        return new MySQLConnection();
    }
}