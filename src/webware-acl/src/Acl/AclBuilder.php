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

namespace Webware\Acl\Acl;

use Laminas\Permissions\Acl\Acl;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\Event\AclBuildStartedEvent;
use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\Event\ResourcesLoadedEvent;
use Webware\Acl\Event\RolesLoadedEvent;
use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\Exception\RuntimeException;
use Webware\Acl\Repository\AclRepositoryInterface;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function sprintf;

/**
 * Builds a fully hydrated Laminas\Permissions\Acl\Acl instance from database
 * data, using FileAclCache to avoid redundant DB queries on every request.
 *
 * On every call to build(), the current acl_version counter is compared
 * against the cached version. A mismatch (or absent cache) triggers a full
 * DB reload and a cache write. A version match returns the Acl rebuilt
 * in-memory from the cached raw arrays — zero DB queries beyond the single
 * fetchVersion() call.
 *
 * PSR-14 events are fired at each stage of the build so that listeners can
 * extend the ACL without modifying this class. Wiring the dispatcher is
 * optional; if none is provided the build proceeds without event dispatch.
 */
final class AclBuilder
{
    /** @var array<string, array{resource_id: string, privilege_id: string}> */
    private array $routeMappings = [];

    public function __construct(
        private readonly AclRepositoryInterface $repository,
        private readonly AclCacheInterface $cache,
        private readonly ?EventDispatcherInterface $events = null,
    ) {
    }

    /**
     * Returns a fully built Laminas Acl instance.
     * Reads from cache when the version matches; re-hydrates from DB otherwise.
     */
    public function build(): Acl
    {
        $currentVersion = $this->repository->fetchVersion();
        $cached         = $this->cache->get();

        if ($cached !== null && (int) $cached['version'] === $currentVersion) {
            return $this->buildFromArrays($cached);
        }

        // Cache miss or stale — load everything from the DB
        $roles         = $this->repository->fetchRoles();
        $parents       = $this->repository->fetchRoleParents();
        $resources     = $this->repository->fetchResources();
        $rules         = $this->repository->fetchRules();
        $routeMappings = $this->repository->fetchRouteMappings();

        // Convert entity objects to cache-friendly arrays
        $rolesData = array_values(
            array_map(
                static fn($r) => ['id' => $r->id, 'role_id' => $r->roleId],
                $roles,
            ),
        );

        $resourcesData = array_values(
            array_map(
                static fn($r) => ['resource_pk' => $r->resourcePk, 'resource_id' => $r->resourceId],
                $resources,
            ),
        );

        $data = [
            'version'       => $currentVersion,
            'roles'         => $rolesData,
            'parents'       => $parents,
            'resources'     => $resourcesData,
            'rules'         => $rules,         // already resolved string IDs from repository joins
            'routeMappings' => $routeMappings,  // already resolved string IDs from repository joins
        ];

        $this->cache->set($data);

        return $this->buildFromArrays($data);
    }

    /**
     * Returns the route→resource+privilege mapping populated by the most
     * recent call to build(). Must be called after build().
     *
     * @return array<string, array{resource_id: string, privilege_id: string}>
     */
    public function getRouteMappings(): array
    {
        return $this->routeMappings;
    }

    /**
     * Constructs the Laminas Acl from a raw data array (either from cache
     * or freshly serialised from DB entities).
     *
     * @param array<string, mixed> $data
     */
    private function buildFromArrays(array $data): Acl
    {
        $acl = new Acl();

        $this->dispatch(new AclBuildStartedEvent($acl));

        // Build PK → role_id map
        $pkToRoleId = [];
        foreach ($data['roles'] as $row) {
            $pkToRoleId[(int) $row['id']] = (string) $row['role_id'];
        }

        // Add roles with parent inheritance in topological order
        $this->addRolesInOrder($acl, $pkToRoleId, $data['parents']);

        $this->dispatch(new RolesLoadedEvent($acl));

        // Add resources (Laminas accepts plain strings for resource_id)
        foreach ($data['resources'] as $row) {
            $acl->addResource((string) $row['resource_id']);
        }

        $this->dispatch(new ResourcesLoadedEvent($acl));

        // Apply allow / deny rules
        foreach ($data['rules'] as $row) {
            if ($row['type'] === 'allow') {
                $acl->allow((string) $row['role_id'], (string) $row['resource_id'], (string) $row['privilege_id']);
            } else {
                $acl->deny((string) $row['role_id'], (string) $row['resource_id'], (string) $row['privilege_id']);
            }
        }

        $this->dispatch(new RulesLoadedEvent($acl));

        // Store route mappings for AclMiddleware access
        $this->routeMappings = $data['routeMappings'];

        $this->dispatch(new AclBuiltEvent($acl));

        return $acl;
    }

    /**
     * Adds roles to the Acl instance in topological order so that every
     * parent role is registered before any of its children.
     *
     * Throws RuntimeException if the inheritance graph contains a cycle or
     * an unresolvable parent reference.
     *
     * @param array<int, string>  $pkToRoleId  Map of role PK → role_id string
     * @param array<int, int[]>   $parentMap   Map of child role PK → list of parent PKs
     */
    private function addRolesInOrder(Acl $acl, array $pkToRoleId, array $parentMap): void
    {
        $added   = [];
        $pending = array_keys($pkToRoleId);

        // Upper bound prevents infinite loops on malformed data
        $maxIterations = count($pending) + 1;
        $iteration     = 0;

        while (! empty($pending) && $iteration++ < $maxIterations) {
            foreach ($pending as $key => $pk) {
                $parentPks    = $parentMap[$pk] ?? [];
                $parentRoleIds = array_map(static fn($ppk) => $pkToRoleId[$ppk], $parentPks);

                // Only add this role once all its parents are already in the Acl
                if (array_diff($parentRoleIds, $added) === []) {
                    $acl->addRole($pkToRoleId[$pk], $parentRoleIds ?: null);
                    $added[] = $pkToRoleId[$pk];
                    unset($pending[$key]);
                }
            }
        }

        if (! empty($pending)) {
            throw new RuntimeException(
                sprintf(
                    'Circular or unresolvable role inheritance detected in ACL configuration. '
                    . 'Affected role PKs: %s',
                    implode(', ', $pending),
                ),
            );
        }
    }

    private function dispatch(object $event): void
    {
        $this->events?->dispatch($event);
    }
}
