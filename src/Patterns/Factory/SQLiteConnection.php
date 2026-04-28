<?php
namespace App\Patterns\Factory;

class SQLiteConnection implements ConnectionInterface {
    public function connect(): bool { return true; }
}