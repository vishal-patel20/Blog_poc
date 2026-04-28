<?php

declare(strict_types=1);
namespace App\Patterns\Observer;

class AuditLogListener {
    public function __invoke(array $data): void {
        // Log audit trail
    }
}