<?php
namespace App\Patterns\Factory;

class SQLiteFactory extends DatabaseFactory {
    public function createConnection(): ConnectionInterface {
        return new SQLiteConnection();
    }
}