<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\RulesLoadedEvent;

/**
 * Registers admin module ACL rules.
 *
 * Grants Administrator and Developer the 'read' privilege on 'admin.dashboard'.
 */
final class RegisterAdminRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Administrator', 'admin.dashboard', 'read');
        $event->acl->allow('Developer', 'admin.dashboard', 'read');
    }
}
