<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * Return the numeric PK for a role by its name, or null if not found.
     */
    public function findRoleIdByName(string $roleName): ?int;

    /**
     * Find a user by their verification token, or null if not found.
     */
    public function findByVerificationToken(string $token): ?User;
}
