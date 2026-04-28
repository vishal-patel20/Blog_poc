<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

class SQLiteConnection implements ConnectionInterface {
    public function connect(): bool { return true; }
}