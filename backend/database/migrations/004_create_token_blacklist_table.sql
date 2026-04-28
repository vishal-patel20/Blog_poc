-- Migration: Create token_blacklist table (for JWT logout / revocation)
-- Created: 2026-04-28

CREATE TABLE IF NOT EXISTS token_blacklist (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    token_hash TEXT    NOT NULL UNIQUE,   -- SHA-256 of the raw JWT
    expires_at INTEGER NOT NULL,          -- Unix timestamp (JWT exp claim)
    created_at TEXT    NOT NULL
);

-- Index for fast O(1) blacklist lookup on every authenticated request
CREATE UNIQUE INDEX IF NOT EXISTS idx_blacklist_hash    ON token_blacklist(token_hash);
-- Index for efficient cleanup of expired tokens
CREATE        INDEX IF NOT EXISTS idx_blacklist_expires ON token_blacklist(expires_at);
