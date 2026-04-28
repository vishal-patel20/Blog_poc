<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

class PostgreSQLFactory extends DatabaseFactory {
    public function createConnection(): ConnectionInterface {
        return new PostgreSQLConnection();
    }
}