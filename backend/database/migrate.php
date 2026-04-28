<?php

declare(strict_types=1);

/**
 * Database migration runner.
 *
 * Run from the project root:
 *   php database/migrate.php
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;

$pdo        = Database::getInstance()->getConnection();
$migrations = glob(__DIR__ . '/migrations/*.sql');

if ($migrations === false) {
    echo "No migration files found.\n";
    exit(1);
}

sort($migrations);

foreach ($migrations as $file) {
    $sql = file_get_contents($file);

    if ($sql === false) {
        echo "Could not read: {$file}\n";
        continue;
    }

    try {
        $pdo->exec($sql);
        echo "Ran: " . basename($file) . "\n";
    } catch (\PDOException $e) {
        // Gracefully skip already-applied ALTER TABLE ADD COLUMN migrations
        if (str_contains($e->getMessage(), 'duplicate column')) {
            echo "Skipped (already applied): " . basename($file) . "\n";
        } else {
            echo "ERROR in " . basename($file) . ": " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

echo "All migrations completed successfully.\n";
