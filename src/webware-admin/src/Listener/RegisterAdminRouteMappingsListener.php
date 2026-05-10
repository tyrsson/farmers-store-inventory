<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\Privilege;

/**
 * Registers route→ACL resource mappings for the admin module.
 *
 * Called on AclBuiltEvent so that AclMiddleware can resolve routes to their
 * required resource and privilege without a database lookup.
 */
final class RegisterAdminRouteMappingsListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->addRouteMapping('admin.dashboard.read', 'admin.dashboard', Privilege::READ);
    }
}
