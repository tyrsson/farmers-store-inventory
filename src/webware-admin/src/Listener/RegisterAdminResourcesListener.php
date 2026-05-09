<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\ResourcesLoadedEvent;

/**
 * Registers admin module ACL resources.
 *
 * Invoked on ResourcesLoadedEvent; adds the admin.dashboard resource to the
 * Acl so that module-level rules and widgets can reference it.
 */
final class RegisterAdminResourcesListener
{
    public function __invoke(ResourcesLoadedEvent $event): void
    {
        $event->acl->addResource('admin.dashboard');
    }
}
