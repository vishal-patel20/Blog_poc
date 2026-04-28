<?php

declare(strict_types=1);
namespace App\Solid\Srp;

/**
 * Single Responsibility Principle (SRP)
 * Problem it solves: A class should have one, and only one, reason to change.
 * Why chosen: Separating database access from validation makes the code modular and easier to test.
 * What breaks if removed: If validation and DB access are in one class, changing validation logic might inadvertently break DB access.
 */
class PostRepository {
    public function save(array $data): bool {
        // Handle DB saving only
        return true;
    }
}