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
     * Each row contains: id (int), role_id (string), resource_id (string),
     * privilege_id (string), type ('allow'|'deny').
     *
     * @return array<int, array{id: int, role_id: string, resource_id: string, privilege_id: string, type: string}>
     */
    public function fetchRules(): array;

    /**
     * Returns assertion rows grouped by rule PK.
     * Each entry: rule_pk (int) → array of rows with assertion (FQCN string),
     * mode ('all'|'at_least_one'), sort_order (int).
     *
     * @return array<int, array<int, array{id: int, assertion: string, mode: string, sort_order: int}>>
     */
    public function fetchRuleAssertions(): array;

    /**
     * Returns the current ACL version counter from the acl_version table.
     * Used by FileAclCache to determine whether the cache is stale.
     */
    public function fetchVersion(): int;

    // -------------------------------------------------------------------------
    // Write methods — all callers must call incrementVersion() afterwards
    // so the FileAclCache is invalidated on the next request.
    // -------------------------------------------------------------------------

    /**
     * Inserts a new role and records its parent inheritance.
     * Returns the new role's integer PK.
     */
    public function insertRole(string $roleId, int $parentPk): int;

    /**
     * Inserts or updates a role. Uses the role's unique key (role_id) to detect
     * an existing record; updates the parent if found, inserts otherwise.
     * Returns the role's integer PK.
     */
    public function saveRole(string $roleId, int $parentPk): int;

    /**
     * Inserts a new ACL resource.
     * Returns the new resource's integer PK.
     */
    public function insertResource(string $resourceId, string $label): int;

    /**
     * Inserts or updates an ACL resource. Uses the unique key (resource_id) to
     * detect an existing record; updates the label if found, inserts otherwise.
     * Returns the resource's integer PK.
     */
    public function saveResource(string $resourceId, string $label): int;

    /**
     * Inserts a new privilege scoped to a resource.
     * Returns the new privilege's integer PK.
     */
    public function insertPrivilege(int $resourcePk, string $privilegeId, string $label): int;

    /**
     * Inserts an allow/deny rule for a role+resource+privilege triple.
     * Uses INSERT … ON DUPLICATE KEY UPDATE to upsert by the unique key
     * (role_pk, resource_pk, privilege_pk).
     */
    public function saveRule(int $rolePk, int $resourcePk, int $privilegePk, string $type): void;

    /**
     * Updates the type ('allow'|'deny') of an existing rule by its integer PK.
     * Used by PATCH from the hierarchy view where the rule id is already known.
     */
    public function updateRuleType(int $id, string $type): void;

    /**
     * Deletes a role and its parent-mapping rows by PK.
     * Caller must ensure no users are assigned to the role before calling this.
     */
    public function deleteRole(int $rolePk): void;

    /**
     * Deletes a resource and all dependent data (rules, route mappings, privileges)
     * in FK-safe order. This is a cascading delete — all rules and route mappings
     * referencing this resource will also be removed.
     */
    public function deleteResource(int $resourcePk): void;

    /**
     * Deletes a rule by its integer PK.
     */
    public function deleteRule(int $id): void;

    /**
     * Inserts a new assertion row for the given rule PK.
     * Returns the new row's integer PK.
     */
    public function saveRuleAssertion(int $rulePk, string $assertion, string $mode, int $sortOrder): int;

    /**
     * Deletes an assertion row by its own integer PK.
     */
    public function deleteRuleAssertion(int $id): void;

    /**
     * Increments the acl_version counter by 1.
     * Must be called after every write so the FileAclCache is invalidated.
     */
    public function incrementVersion(): void;
}
