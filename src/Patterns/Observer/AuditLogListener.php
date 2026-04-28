<?php
namespace App\Patterns\Observer;

class AuditLogListener {
    public function __invoke(array $data): void {
        // Log audit trail
    }
}