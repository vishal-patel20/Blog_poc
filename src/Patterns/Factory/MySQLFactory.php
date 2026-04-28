<?php
namespace App\Patterns\Factory;

class MySQLFactory extends DatabaseFactory {
    public function createConnection(): ConnectionInterface {
        return new MySQLConnection();
    }
}