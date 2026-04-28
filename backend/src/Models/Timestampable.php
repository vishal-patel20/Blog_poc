<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Timestampable trait.
 *
 * Adds created_at and updated_at timestamp management to any model that
 * uses it. Both properties are stored as ISO-8601 date-time strings.
 */
trait Timestampable
{
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;

    /**
     * Initialise timestamps to the current UTC time.
     */
    public function initTimestamps(): void
    {
        $now             = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Refresh the updatedAt timestamp to the current UTC time.
     */
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
