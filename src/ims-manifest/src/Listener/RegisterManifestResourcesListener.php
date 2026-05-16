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

use Webware\Acl\Event\ResourcesLoadedEvent;

/**
 * Previously registered the 'manifest' and 'admin.manifest' ACL resources
 * inline. Both resources are now seeded as system rows in acl_resource
 * (999_seed.sql) and loaded by AclBuilder::fetchResources() before any event
 * fires. Adding them here a second time would throw "Resource already exists".
 *
 * This listener is retained as a no-op so the ConfigProvider registration and
 * event wiring do not need to change. It may be removed in a future cleanup
 * once all callers are audited.
 */
final class RegisterManifestResourcesListener
{
    public function __invoke(ResourcesLoadedEvent $event): void
    {
        // No-op: 'manifest' and 'admin.manifest' are seeded in acl_resource
        // with system = 1. AclBuilder loads them from DB before this event fires.
    }
}
