<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Cache;

interface AclCacheInterface
{
    /**
     * Returns the cached raw data array, or null if no cache file exists.
     *
     * The array shape is:
     * [
     *   'version'       => int,
     *   'roles'         => list<array{id: int, role_id: string}>,
     *   'parents'       => array<int, int[]>,
     *   'resources'     => list<array{resource_pk: int, resource_id: string}>,
     *   'rules'         => list<array{role_id: string, resource_id: string, privilege_id: string, type: string}>,
     *   'routeMappings' => array<string, array{resource_id: string, privilege_id: string}>,
     * ]
     *
     * @return array<string, mixed>|null
     */
    public function get(): ?array;

    /**
     * Persists the raw data array to the cache store.
     *
     * @param array<string, mixed> $data
     */
    public function set(array $data): void;

    /**
     * Removes the cache file so the next build re-reads from the database.
     */
    public function invalidate(): void;
}
