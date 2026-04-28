<?php
namespace App\Patterns\Observer;

class EmailNotificationListener {
    public function __invoke(array $data): void {
        // Send email logic
    }
}