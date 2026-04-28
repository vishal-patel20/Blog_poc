<?php
namespace App\Patterns\Factory;

class PostgreSQLFactory extends DatabaseFactory {
    public function createConnection(): ConnectionInterface {
        return new PostgreSQLConnection();
    }
}