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

namespace Ims\Manifest\Repository;

use Ims\Manifest\Entity\Manifest;

interface ManifestRepositoryInterface
{
    /**
     * Return a paginated list of manifests, most-recent first.
     *
     * @return Manifest[]
     */
    public function findAll(int $limit = 25, int $offset = 0): array;

    /**
     * Count all manifests (for pagination).
     */
    public function countAll(): int;

    /**
     * Find a manifest by its primary key, including all items.
     * Returns null when not found.
     */
    public function findById(int $id): ?Manifest;

    /**
     * Persist a new manifest row and return the generated id.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int;
}
