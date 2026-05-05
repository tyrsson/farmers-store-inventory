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

namespace Webware\Acl\Repository;

use Webware\Acl\Entity\Privilege;
use Webware\Acl\Entity\Resource;
use Webware\Acl\Entity\Role;

interface AclRepositoryInterface
{
    /**
     * Returns all roles indexed by their integer PK.
     *
     * @return array<int, Role>
     */
    public function fetchRoles(): array;

    /**
     * Returns the role inheritance map: child role PK → list of parent role PKs.
     *
     * @return array<int, int[]>
     */
    public function fetchRoleParents(): array;

    /**
     * Returns all resources indexed by their integer PK.
     *
     * @return array<int, Resource>
     */
    public function fetchResources(): array;

    /**
     * Returns all privileges indexed by their integer PK.
     *
     * @return array<int, Privilege>
     */
    public function fetchPrivileges(): array;

    /**
     * Returns all ACL rules as raw row arrays.
     * Each row contains: role_id (string), resource_id (string),
     * privilege_id (string), type ('allow'|'deny').
     *
     * @return array<int, array{role_id: string, resource_id: string, privilege_id: string, type: string}>
     */
    public function fetchRules(): array;

    /**
     * Returns route→resource+privilege mappings.
     * Each row: route_name, resource_id (string), privilege_id (string).
     *
     * @return array<string, array{resource_id: string, privilege_id: string}>
     */
    public function fetchRouteMappings(): array;

    /**
     * Returns the current ACL version counter from the acl_version table.
     * Used by FileAclCache to determine whether the cache is stale.
     */
    public function fetchVersion(): int;
}
