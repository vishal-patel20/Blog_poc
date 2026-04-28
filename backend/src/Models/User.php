<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User model — represents a registered user account.
 *
 * @property int    $id
 * @property string $name
 * @property string $email
 * @property string $role   admin|author|reader
 *
 * NOTE: password is NEVER included in toArray() to prevent accidental exposure.
 */
final class User extends BaseModel
{
    protected string $table = 'users';

    public function __construct(
        private string $name,
        private string $email,
        private string $password,   // bcrypt hash
        private string $role = 'reader',
    ) {
    }

    // ------------------------------------------------------------------
    // Factory
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $user = new self(
            name:     (string) $row['name'],
            email:    (string) $row['email'],
            password: (string) $row['password'],
            role:     (string) ($row['role'] ?? 'reader'),
        );

        $user->setId((int) $row['id']);
        $user->hydrateTimestamps($row);

        return $user;
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getName(): string    { return $this->name; }
    public function getEmail(): string   { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): string    { return $this->role; }
    public function getTable(): string   { return $this->table; }

    /**
     * Safe public representation — password intentionally excluded.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
