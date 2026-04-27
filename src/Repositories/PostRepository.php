<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Models\BaseModel;
use App\Models\Post;
use PDO;

/**
 * PostRepository — data-access layer for blog posts.
 *
 * Implements RepositoryInterface. Handles soft-deletes; deleted posts
 * are excluded from findAll but remain in the database.
 */
class PostRepository implements RepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Find a non-deleted post by ID.
     *
     * @throws NotFoundException
     */
    public function findById(int $id): ?Post
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM posts WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        if ($row === false) {
            throw new NotFoundException("Post with ID {$id} not found.");
        }

        return Post::fromRow($row);
    }

    /**
     * Return a paginated list of non-deleted posts.
     *
     * @return list<Post>
     */
    public function findAll(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM posts WHERE deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return array_map(
            static fn (array $row) => Post::fromRow($row),
            $rows
        );
    }

    /**
     * Return the total count of non-deleted posts.
     */
    public function count(): int
    {
        return (int) $this->pdo
            ->query('SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL')
            ->fetchColumn();
    }

    /**
     * Insert a new post and return its new ID.
     */
    public function save(BaseModel $model): int
    {
        assert($model instanceof Post);
        $model->initTimestamps();

        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (title, body, status, deleted_at, created_at, updated_at)
             VALUES (:title, :body, :status, :deleted_at, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':title'      => $model->getTitle(),
            ':body'       => $model->getBody(),
            ':status'     => $model->getStatus(),
            ':deleted_at' => $model->getDeletedAt(),
            ':created_at' => $model->getCreatedAt(),
            ':updated_at' => $model->getUpdatedAt(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $model->setId($id);

        return $id;
    }

    /**
     * Update all mutable fields of an existing post.
     */
    public function update(BaseModel $model): bool
    {
        assert($model instanceof Post);
        $model->touchUpdatedAt();

        $stmt = $this->pdo->prepare(
            'UPDATE posts
             SET title = :title, body = :body, status = :status,
                 deleted_at = :deleted_at, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL'
        );

        return $stmt->execute([
            ':title'      => $model->getTitle(),
            ':body'       => $model->getBody(),
            ':status'     => $model->getStatus(),
            ':deleted_at' => $model->getDeletedAt(),
            ':updated_at' => $model->getUpdatedAt(),
            ':id'         => $model->getId(),
        ]);
    }

    /**
     * Soft-delete a post by setting deleted_at to the current timestamp.
     */
    public function delete(int $id): bool
    {
        $post = $this->findById($id); // throws NotFoundException if not found
        $post->softDelete();

        $stmt = $this->pdo->prepare(
            'UPDATE posts SET deleted_at = :deleted_at WHERE id = :id'
        );

        return $stmt->execute([
            ':deleted_at' => $post->getDeletedAt(),
            ':id'         => $id,
        ]);
    }
}
