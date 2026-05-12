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
 * Registers the manifest ACL resource.
 *
 * Invoked on ResourcesLoadedEvent so that rules and route mappings can
 * reference the 'manifest' resource.
 */
final class RegisterManifestResourcesListener
{
    public function __invoke(ResourcesLoadedEvent $event): void
    {
        $event->acl->addResource('manifest');
        $event->acl->addResource('admin.manifest');
    }
}
