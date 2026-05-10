<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\Privilege;

/**
 * Registers admin module ACL rules.
 *
 * Grants Administrator and Developer read on admin.dashboard.
 */
final class RegisterAdminRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Administrator', 'admin.dashboard', Privilege::READ);
        $event->acl->allow('Developer', 'admin.dashboard', Privilege::READ);
    }
}
