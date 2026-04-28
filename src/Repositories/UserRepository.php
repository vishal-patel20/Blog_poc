<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Models\BaseModel;
use App\Models\User;
use PDO;

/**
 * UserRepository — data-access layer for user accounts.
 */
class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ------------------------------------------------------------------
    // Queries
    // ------------------------------------------------------------------

    /**
     * @throws NotFoundException
     */
    public function findById(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw new NotFoundException('User not found.');
        }

        return User::fromRow($row);
    }

    /**
     * @throws NotFoundException
     */
    public function findByEmail(string $email): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        if ($row === false) {
            throw new NotFoundException('User not found.');
        }

        return User::fromRow($row);
    }

    /**
     * Check if an email is already registered.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute([':email' => strtolower(trim($email))]);

        return $stmt->fetchColumn() !== false;
    }

    // ------------------------------------------------------------------
    // Mutations
    // ------------------------------------------------------------------

    /**
     * Persist a new user and return their new ID.
     */
    public function save(BaseModel $model): int
    {
        if (!$model instanceof User) {
            throw new \InvalidArgumentException(
                sprintf('Expected User, got %s.', get_class($model))
            );
        }

        $model->initTimestamps();

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, created_at, updated_at)
             VALUES (:name, :email, :password, :role, :created_at, :updated_at)'
        );

        $stmt->execute([
            ':name'       => $model->getName(),
            ':email'      => strtolower(trim($model->getEmail())),
            ':password'   => $model->getPassword(),
            ':role'       => $model->getRole(),
            ':created_at' => $model->getCreatedAt(),
            ':updated_at' => $model->getUpdatedAt(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $model->setId($id);

        return $id;
    }

    public function update(BaseModel $model): bool { return false; } // not needed yet
    public function delete(int $id): bool          { return false; } // not needed yet
    public function findAll(int $page = 1, int $perPage = 10): array { return []; }
}
