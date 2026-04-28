<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

class MySQLConnection implements ConnectionInterface {
    public function connect(): bool { return true; }
}