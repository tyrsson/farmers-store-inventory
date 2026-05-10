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

namespace Ims\Manifest\Listener;

use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\Privilege;

/**
 * Grants ACL rules for the manifest resource.
 *
 * Warehouse is the lowest role that processes manifests. ACL inheritance
 * propagates the allow upward: Warehouse Supervisor, DC Warehouse, Manager,
 * Administrator, Developer all gain access automatically.
 *
 * Sales is a sibling of Warehouse under member and is intentionally excluded
 * — sales staff do not process inbound manifests.
 */
final class RegisterManifestRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Warehouse', 'manifest', [Privilege::READ, Privilege::CREATE, Privilege::UPDATE]);
        // admin.manifest rules are TBD — Warehouse Supervisor is the minimum candidate.
        // Grant READ so the admin widget is visible to that role and above.
        $event->acl->allow('Warehouse Supervisor', 'admin.manifest', Privilege::READ);
    }
}
