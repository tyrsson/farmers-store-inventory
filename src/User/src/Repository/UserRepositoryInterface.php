<?php

declare(strict_types=1);

namespace User\Repository;

use Mezzio\Authentication\UserRepositoryInterface as UserRepositoryContract;
use User\Entity\User;

interface UserRepositoryInterface extends UserRepositoryContract
{
    /**
     * Find a user by their email address, or null if not found.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their primary key, or null if not found.
     */
    public function findById(int $id): ?User;

    /**
     * Return all users, optionally filtered to a specific store.
     *
     * @return User[]
     */
    public function findAll(?int $storeId = null): array;

    /**
     * Persist a new user row and return the generated id.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int;

    /**
     * Update an existing user row.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void;
}
