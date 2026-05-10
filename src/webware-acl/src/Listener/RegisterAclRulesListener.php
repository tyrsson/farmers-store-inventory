<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\Privilege;

/**
 * Registers webware-acl module ACL rules.
 *
 * Grants Developer all privileges on admin.acl. Administrator is intentionally
 * excluded — granting Administrators ACL write access would allow them to lock
 * themselves and other Administrators out of the system.
 */
final class RegisterAclRulesListener
{
    public function __invoke(RulesLoadedEvent $event): void
    {
        $event->acl->allow('Developer', 'admin.acl', [Privilege::READ, Privilege::CREATE, Privilege::UPDATE, Privilege::DELETE]);
    }
}
