-- Migration: Create posts table
-- Created: 2026-04-27

CREATE TABLE IF NOT EXISTS posts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT    NOT NULL,
    body       TEXT    NOT NULL,
    status     TEXT    NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
    deleted_at TEXT    DEFAULT NULL,
    created_at TEXT    NOT NULL,
    updated_at TEXT    NOT NULL
);
