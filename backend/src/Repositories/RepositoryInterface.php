<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

/**
 * RepositoryInterface — contract for all data-access repositories.
 *
 * Every repository must support the standard CRUD operations defined here.
 */
interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     */
    public function findById(int $id): ?BaseModel;

    /**
     * Return all records, optionally paginated.
     *
     * @param int $page     1-based page number.
     * @param int $perPage  Items per page.
     * @return list<BaseModel>
     */
    public function findAll(int $page = 1, int $perPage = 10): array;

    /**
     * Persist a new record and return its auto-incremented ID.
     */
    public function save(BaseModel $model): int;

    /**
     * Persist changes to an existing record.
     */
    public function update(BaseModel $model): bool;

    /**
     * Remove a record permanently or soft-delete it.
     */
    public function delete(int $id): bool;
}
