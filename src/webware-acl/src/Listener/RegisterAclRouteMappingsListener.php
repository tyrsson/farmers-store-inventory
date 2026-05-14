<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\PrivilegeInterface;

/**
 * Registers route→ACL resource mappings for the webware-acl management routes.
 *
 * Called on AclBuiltEvent so that AuthorizationMiddleware can resolve
 * admin.acl.* routes to their required resource and privilege without a
 * database lookup.
 */
final class RegisterAclRouteMappingsListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->addRouteMapping('admin.acl.read',              'admin.acl', PrivilegeInterface::READ);
        $event->addRouteMapping('admin.acl.routes.read',        'admin.acl', PrivilegeInterface::READ);
        $event->addRouteMapping('admin.acl.roles.read',         'admin.acl', PrivilegeInterface::READ);
        $event->addRouteMapping('admin.acl.resources.read',     'admin.acl', PrivilegeInterface::READ);
        $event->addRouteMapping('admin.acl.rules.read',         'admin.acl', PrivilegeInterface::READ);
        $event->addRouteMapping('admin.acl.rules.create',       'admin.acl', PrivilegeInterface::CREATE);
        $event->addRouteMapping('admin.acl.rules.update',       'admin.acl', PrivilegeInterface::UPDATE);
        $event->addRouteMapping('admin.acl.rules.delete',       'admin.acl', PrivilegeInterface::DELETE);
        $event->addRouteMapping('admin.acl.routes.create',      'admin.acl', PrivilegeInterface::CREATE);
        $event->addRouteMapping('admin.acl.routes.delete',      'admin.acl', PrivilegeInterface::DELETE);
        $event->addRouteMapping('admin.acl.roles.create',       'admin.acl', PrivilegeInterface::CREATE);
        $event->addRouteMapping('admin.acl.roles.delete',       'admin.acl', PrivilegeInterface::DELETE);
        $event->addRouteMapping('admin.acl.resources.create',   'admin.acl', PrivilegeInterface::CREATE);
        $event->addRouteMapping('admin.acl.resources.delete',   'admin.acl', PrivilegeInterface::DELETE);
        $event->addRouteMapping('admin.acl.assertions.create',  'admin.acl', PrivilegeInterface::CREATE);
        $event->addRouteMapping('admin.acl.assertions.delete',  'admin.acl', PrivilegeInterface::DELETE);
    }
}
