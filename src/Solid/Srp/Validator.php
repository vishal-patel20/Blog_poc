<?php
namespace App\Solid\Srp;

/**
 * Single Responsibility Principle (SRP)
 */
class Validator {
    public function validate(array $data): bool {
        // Handle validation only
        return !empty($data['title']);
    }
}