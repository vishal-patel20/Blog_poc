<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Base test case that sets up an in-memory SQLite database.
 *
 * Each test gets a fresh database so tests are isolated.
 */
abstract class BaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Point Database singleton at a fresh in-memory database
        $this->bootInMemoryDatabase();
    }

    private function bootInMemoryDatabase(): void
    {
        // Re-create the singleton with an in-memory PDO
        $reflection = new \ReflectionClass(Database::class);

        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setValue(null, null); // reset singleton

        // Override the singleton with an in-memory version
        $instance = $reflection->newInstanceWithoutConstructor();

        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Run migrations
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS posts (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT    NOT NULL,
                body       TEXT    NOT NULL,
                status     TEXT    NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
                author_id  INTEGER DEFAULT NULL,
                deleted_at TEXT    DEFAULT NULL,
                created_at TEXT    NOT NULL,
                updated_at TEXT    NOT NULL
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS comments (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id    INTEGER NOT NULL,
                author     TEXT    NOT NULL,
                body       TEXT    NOT NULL,
                created_at TEXT    NOT NULL,
                updated_at TEXT    NOT NULL
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                email      TEXT    NOT NULL UNIQUE,
                password   TEXT    NOT NULL,
                role       TEXT    NOT NULL DEFAULT 'reader'
                           CHECK (role IN ('admin', 'author', 'reader')),
                created_at TEXT    NOT NULL,
                updated_at TEXT    NOT NULL
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS token_blacklist (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                token_hash TEXT    NOT NULL UNIQUE,
                expires_at INTEGER NOT NULL,
                created_at TEXT    NOT NULL
            )"
        );

        $pdoProp = $reflection->getProperty('pdo');
        $pdoProp->setValue($instance, $pdo);

        $instanceProp->setValue(null, $instance);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset the singleton after each test
        $reflection   = new \ReflectionClass(Database::class);
        $instanceProp = $reflection->getProperty('instance');
        $instanceProp->setValue(null, null);
    }
}
