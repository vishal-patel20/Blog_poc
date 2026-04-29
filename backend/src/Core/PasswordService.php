<?php

declare(strict_types=1);

namespace App\Core;

final class PasswordService
{
    private const ALGORITHM = PASSWORD_ARGON2ID;
    private const OPTIONS   = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];

    public function hash(string $plaintext): string
    {
        return password_hash($plaintext, self::ALGORITHM, self::OPTIONS);
    }

    public function verify(string $plaintext, string $hash): bool
    {
        return password_verify($plaintext, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::ALGORITHM, self::OPTIONS);
    }
}
