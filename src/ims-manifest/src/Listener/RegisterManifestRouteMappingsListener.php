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

use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\PrivilegeInterface;

/**
 * Registers route → ACL resource mappings for the manifest module.
 *
 * Called on AclBuiltEvent so AuthorizationMiddleware can resolve route names
 * to their required resource and privilege without a DB lookup.
 */
final class RegisterManifestRouteMappingsListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->addRouteMapping('manifest.list',         'manifest', PrivilegeInterface::READ);
        $event->addRouteMapping('manifest.detail',       'manifest', PrivilegeInterface::READ);
        $event->addRouteMapping('manifest.upload',       'manifest', PrivilegeInterface::READ);
        $event->addRouteMapping('manifest.upload.store', 'manifest', PrivilegeInterface::CREATE);
    }
}
