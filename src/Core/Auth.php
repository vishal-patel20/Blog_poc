<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use RuntimeException;

/**
 * Auth — JWT generation, validation, blacklisting, and password hashing.
 *
 * JWT payload shape:
 *   sub   (int)    — user ID
 *   email (string) — user email
 *   name  (string) — user display name
 *   role  (string) — admin|author|reader
 *   iat   (int)    — issued-at Unix timestamp
 *   exp   (int)    — expiry Unix timestamp
 *   jti   (string) — unique token ID (for blacklisting)
 */
class Auth
{
    private const ALGORITHM = 'HS256';
    public  const TTL       = 3600;   // Token lifetime: 1 hour

    // ------------------------------------------------------------------
    // Token generation
    // ------------------------------------------------------------------

    /**
     * Generate a signed JWT for the given user fields.
     */
    public static function generateToken(int $userId, string $email, string $name, string $role): string
    {
        $secret = self::requireSecret();
        $now    = time();

        $payload = [
            'sub'   => $userId,
            'email' => $email,
            'name'  => $name,
            'role'  => $role,
            'iat'   => $now,
            'exp'   => $now + self::TTL,
            'jti'   => bin2hex(random_bytes(8)), // unique per token
        ];

        return JWT::encode($payload, $secret, self::ALGORITHM);
    }

    // ------------------------------------------------------------------
    // Token validation
    // ------------------------------------------------------------------

    /**
     * Decode and verify a JWT. Throws on invalid/expired tokens.
     *
     * @throws \Firebase\JWT\ExpiredException
     * @throws \Firebase\JWT\SignatureInvalidException
     * @throws \UnexpectedValueException
     */
    public static function decodeToken(string $token): object
    {
        return JWT::decode($token, new Key(self::requireSecret(), self::ALGORITHM));
    }

    /**
     * Extract the raw Bearer token from an Authorization header value.
     */
    public static function extractBearer(string $header): string
    {
        return trim((string) preg_replace('/^Bearer\s+/i', '', $header));
    }

    // ------------------------------------------------------------------
    // Token blacklist (logout / revocation)
    // ------------------------------------------------------------------

    /**
     * Blacklist a token so it cannot be used after logout.
     */
    public static function blacklistToken(string $token, int $expiresAt): void
    {
        $hash = self::tokenHash($token);
        $now  = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);

        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO token_blacklist (token_hash, expires_at, created_at)
             VALUES (:hash, :exp, :now)'
        );
        $stmt->execute([':hash' => $hash, ':exp' => $expiresAt, ':now' => $now]);

        // Opportunistically purge tokens that have already expired
        self::cleanExpiredTokens($pdo);
    }

    /**
     * Return true if the token has been revoked (logged out).
     */
    public static function isBlacklisted(string $token): bool
    {
        $hash = self::tokenHash($token);
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM token_blacklist WHERE token_hash = :hash AND expires_at > :now LIMIT 1'
        );
        $stmt->execute([':hash' => $hash, ':now' => time()]);

        return $stmt->fetchColumn() !== false;
    }

    // ------------------------------------------------------------------
    // Password helpers
    // ------------------------------------------------------------------

    /**
     * Hash a plaintext password with bcrypt (cost 12).
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a plaintext password against a bcrypt hash.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private static function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    private static function requireSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? null;
        if ($secret === null || $secret === '') {
            throw new RuntimeException('JWT_SECRET is not configured.');
        }
        return $secret;
    }

    private static function cleanExpiredTokens(\PDO $pdo): void
    {
        $pdo->prepare('DELETE FROM token_blacklist WHERE expires_at <= :now')
            ->execute([':now' => time()]);
    }
}
