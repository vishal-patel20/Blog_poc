<?php
namespace App\Patterns\Factory;

class MySQLConnection implements ConnectionInterface {
    public function connect(): bool { return true; }
}