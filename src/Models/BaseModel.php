<?php

declare(strict_types=1);

namespace App\Models;

/**
 * BaseModel — abstract base for all models.
 *
 * Provides common properties shared across every entity and enforces
 * a contract for converting a model into an array suitable for JSON output.
 */
abstract class BaseModel
{
    use Timestampable;

    protected ?int $id = null;

    /**
     * Return the underlying database table name.
     */
    abstract public function getTable(): string;

    /**
     * Serialise the model to an associative array for JSON output.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Hydrate timestamps from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    protected function hydrateTimestamps(array $row): void
    {
        $this->createdAt = $row['created_at'] ?? null;
        $this->updatedAt = $row['updated_at'] ?? null;
    }
}
