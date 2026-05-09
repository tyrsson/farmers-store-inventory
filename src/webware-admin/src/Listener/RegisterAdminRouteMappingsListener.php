<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\AclBuiltEvent;

/**
 * Registers the admin.dashboard route→ACL resource mapping.
 *
 * Called on AclBuiltEvent so that AclMiddleware can resolve the route to
 * its required resource and privilege without a database lookup.
 */
final class RegisterAdminRouteMappingsListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->addRouteMapping('admin.dashboard', 'admin.dashboard', 'read');
    }
}
