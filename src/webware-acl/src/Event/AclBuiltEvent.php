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

namespace Webware\Acl\Event;

use Laminas\Permissions\Acl\Acl;

/**
 * Fired after the Acl is fully built, before the cache is written.
 * Listeners may add last-minute rules or register additional route→resource
 * mappings via addRouteMapping(). After dispatch, AclBuilder reads back the
 * accumulated mappings via getRouteMappings().
 */
final class AclBuiltEvent
{
    /** @var array<string, array{resource_id: string, privilege_id: string}> */
    private array $routeMappings;

    /**
     * @param array<string, array{resource_id: string, privilege_id: string}> $routeMappings
     *   DB-loaded route mappings passed in by AclBuilder; listeners may extend this set.
     */
    public function __construct(
        public readonly Acl $acl,
        array $routeMappings = [],
    ) {
        $this->routeMappings = $routeMappings;
    }

    /**
     * Registers an additional route→ACL resource+privilege mapping.
     * Called by listeners (e.g. RegisterAdminRouteMappingsListener).
     */
    public function addRouteMapping(string $routeName, string $resourceId, string $privilegeId): void
    {
        $this->routeMappings[$routeName] = [
            'resource_id'  => $resourceId,
            'privilege_id' => $privilegeId,
        ];
    }

    /**
     * Returns the full route mapping array — DB-loaded plus any added by listeners.
     *
     * @return array<string, array{resource_id: string, privilege_id: string}>
     */
    public function getRouteMappings(): array
    {
        return $this->routeMappings;
    }
}
