<?php

declare(strict_types=1);
namespace App\Patterns\Factory;

/**
 * Factory Method Pattern
 * Problem it solves: Need to create different database connections based on config, keeping calling code decoupled from specific drivers.
 * Why chosen: Encapsulates creation logic. Calling code depends on interfaces, not concrete connection classes.
 * What breaks if removed: Client code would need 'new MySQLConnection()' everywhere, making switching databases extremely hard.
 */
interface ConnectionInterface {
    public function connect(): bool;
}