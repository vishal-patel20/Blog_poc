<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Models\BaseModel;
use App\Models\Comment;
use PDO;

/**
 * CommentRepository — data-access layer for comments.
 *
 * Implements RepositoryInterface. Comments are scoped to a parent post.
 */
class CommentRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Find a single comment by ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): ?Comment
    {
        $stmt = $this->pdo->prepare('SELECT * FROM comments WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        if ($row === false) {
            // Vulnerability Fix #13: Generic message — do not echo the ID back.
            throw new NotFoundException('Comment not found.');
        }

        return Comment::fromRow($row);
    }

    /**
     * Return all comments (no pagination required per spec).
     *
     * @return list<Comment>
     */
    public function findAll(int $page = 1, int $perPage = 100): array
    {
        // Vulnerability Fix #11: Apply LIMIT/OFFSET so the method honours its
        // $page/$perPage parameters. Previously all rows were fetched regardless.
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM comments ORDER BY created_at ASC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row) => Comment::fromRow($row),
            $stmt->fetchAll()
        );
    }

    /**
     * Return all comments that belong to a specific post.
     *
     * @return list<Comment>
     */
    public function findByPostId(int $postId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM comments WHERE post_id = :post_id ORDER BY created_at ASC'
        );
        $stmt->execute([':post_id' => $postId]);

        return array_map(
            static fn (array $row) => Comment::fromRow($row),
            $stmt->fetchAll()
        );
    }

    /**
     * Insert a new comment and return its new ID.
     */
    public function save(BaseModel $model): int
    {
        // Vulnerability Fix #10: Replace assert() — silently disabled when
        // zend.assertions=-1 (production). Use an explicit guard instead.
        if (!$model instanceof Comment) {
            throw new \InvalidArgumentException(
                sprintf('Expected Comment, got %s.', get_class($model))
            );
        }
        $model->initTimestamps();

        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (post_id, author, body, created_at, updated_at)
             VALUES (:post_id, :author, :body, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':post_id'    => $model->getPostId(),
            ':author'     => $model->getAuthor(),
            ':body'       => $model->getBody(),
            ':created_at' => $model->getCreatedAt(),
            ':updated_at' => $model->getUpdatedAt(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $model->setId($id);

        return $id;
    }

    /**
     * Update an existing comment (not required by API spec but satisfies interface).
     */
    public function update(BaseModel $model): bool
    {
        // Vulnerability Fix #10: Replace assert() with explicit type guard.
        if (!$model instanceof Comment) {
            throw new \InvalidArgumentException(
                sprintf('Expected Comment, got %s.', get_class($model))
            );
        }
        $model->touchUpdatedAt();

        $stmt = $this->pdo->prepare(
            'UPDATE comments SET author = :author, body = :body, updated_at = :updated_at
             WHERE id = :id'
        );

        return $stmt->execute([
            ':author'     => $model->getAuthor(),
            ':body'       => $model->getBody(),
            ':updated_at' => $model->getUpdatedAt(),
            ':id'         => $model->getId(),
        ]);
    }

    /**
     * Delete a comment permanently.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
