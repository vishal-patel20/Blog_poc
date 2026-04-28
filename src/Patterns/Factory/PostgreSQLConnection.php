<?php
namespace App\Patterns\Factory;

class PostgreSQLConnection implements ConnectionInterface {
    public function connect(): bool { return true; }
}