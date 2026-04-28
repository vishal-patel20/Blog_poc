<?php
namespace App\Patterns\Factory;

abstract class DatabaseFactory {
    abstract public function createConnection(): ConnectionInterface;
}