<?php

declare(strict_types=1);
namespace App\Patterns\Observer;

class EmailNotificationListener {
    public function __invoke(array $data): void {
        // Send email logic
    }
}