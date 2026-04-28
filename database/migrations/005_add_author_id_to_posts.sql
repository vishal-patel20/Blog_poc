-- Migration: Add author_id column to posts table
-- Created: 2026-04-28
-- Links each post to the user who created it (for RBAC ownership checks)

ALTER TABLE posts ADD COLUMN author_id INTEGER REFERENCES users(id);
