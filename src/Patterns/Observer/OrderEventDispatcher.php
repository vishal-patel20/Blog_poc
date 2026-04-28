<?php

declare(strict_types=1);
namespace App\Patterns\Observer;

/**
 * Observer Pattern
 * Problem it solves: Need to trigger multiple actions (email, inventory, audit) when an order event happens without tight coupling.
 * Why chosen: Allows dynamic registration of listeners. Adding a new listener requires zero changes to the dispatcher.
 * What breaks if removed: Dispatcher would need hardcoded method calls for every action, violating SRP and OCP.
 */
class OrderEventDispatcher {
    private array $listeners = [];

    public function register(string $eventName, callable $listener): void {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(string $eventName, array $data): void {
        if (!isset($this->listeners[$eventName])) return;
        foreach ($this->listeners[$eventName] as $listener) {
            $listener($data);
        }
    }
}