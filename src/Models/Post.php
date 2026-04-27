<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Post model — represents a single blog post entity.
 *
 * @property int    $id
 * @property string $title
 * @property string $body
 * @property string $status   draft|published|archived
 */
final class Post extends BaseModel
{
    protected string $table = 'posts';

    private ?string $deletedAt = null;

    public function __construct(
        private string $title,
        private string $body,
        private string $status = 'draft',
    ) {
    }

    // ------------------------------------------------------------------
    // Factory
    // ------------------------------------------------------------------

    /**
     * Hydrate a Post instance from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $post = new self(
            title:  (string) $row['title'],
            body:   (string) $row['body'],
            status: (string) ($row['status'] ?? 'draft'),
        );

        $post->setId((int) $row['id']);
        $post->hydrateTimestamps($row);
        $post->deletedAt = $row['deleted_at'] ?? null;

        return $post;
    }

    // ------------------------------------------------------------------
    // Domain behaviour
    // ------------------------------------------------------------------

    /**
     * Transition the post status to 'published'.
     */
    public function publish(): void
    {
        $this->status = 'published';
    }

    /**
     * Mark the post as soft-deleted.
     */
    public function softDelete(): void
    {
        $this->deletedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->format(\DateTimeInterface::ATOM);
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ------------------------------------------------------------------
    // BaseModel contract
    // ------------------------------------------------------------------

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'body'       => $this->body,
            'status'     => $this->status,
            'deleted_at' => $this->deletedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
