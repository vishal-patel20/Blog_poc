<?php
namespace App\Patterns\Observer;

class InventoryUpdateListener {
    public function __invoke(array $data): void {
        // Update inventory logic
    }
}