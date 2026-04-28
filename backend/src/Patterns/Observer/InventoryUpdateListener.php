<?php

declare(strict_types=1);
namespace App\Patterns\Observer;

class InventoryUpdateListener {
    public function __invoke(array $data): void {
        // Update inventory logic
    }
}