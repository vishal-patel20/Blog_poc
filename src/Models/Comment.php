<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Comment model — represents a comment attached to a blog post.
 *
 * @property int    $id
 * @property int    $postId
 * @property string $author
 * @property string $body
 */
final class Comment extends BaseModel
{
    protected string $table = 'comments';

    public function __construct(
        private int $postId,
        private string $author,
        private string $body,
    ) {
    }

    // ------------------------------------------------------------------
    // Factory
    // ------------------------------------------------------------------

    /**
     * Hydrate a Comment instance from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $comment = new self(
            postId: (int) $row['post_id'],
            author: (string) $row['author'],
            body:   (string) $row['body'],
        );

        $comment->setId((int) $row['id']);
        $comment->hydrateTimestamps($row);

        return $comment;
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getBody(): string
    {
        return $this->body;
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
            'post_id'    => $this->postId,
            'author'     => $this->author,
            'body'       => $this->body,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
