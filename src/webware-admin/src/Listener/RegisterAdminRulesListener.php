<?php

declare(strict_types=1);


namespace Webware\Admin\Listener;

use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\PrivilegeInterface;

/**
 * Registers admin module ACL rules.
 *
 * Grants Warehouse Supervisor and above read on admin.dashboard.
 * Warehouse Supervisor is the minimum role that may access any admin section
 * (manifest admin widget). Inheritance propagates upward through DC Warehouse,
 * Manager, Administrator, and Developer automatically.
 */
final class RegisterAdminRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Warehouse Supervisor', 'admin.dashboard', PrivilegeInterface::READ);
    }
}
