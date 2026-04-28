<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database — PDO Singleton.
 *
 * Ensures only one PDO connection is created during the entire
 * request lifecycle (Singleton pattern).
 */
class Database
{
    private static ?self $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — use Database::getInstance() instead.
     */
    private function __construct()
    {
        $dbPath = dirname(__DIR__, 2) . '/database/database.sqlite';

        // Ensure the database directory exists
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        try {
            $this->pdo = new PDO(
                dsn:      "sqlite:{$dbPath}",
                options:  [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            // Vulnerability Fix #9: Log full detail internally; expose only a generic
            // message to callers to prevent absolute DB file path disclosure.
            error_log('[Blog API] Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }

    /**
     * Return the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Expose the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Prevent cloning of the singleton.
     */
    private function __clone(): void
    {
    }
}
