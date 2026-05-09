<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Laminas\Permissions\Acl\Assertion\OwnershipAssertion;
use Webware\Acl\Event\AclBuiltEvent;

/**
 * Registers the OwnershipAssertion for the user/update rule.
 *
 * Invoked on AclBuiltEvent after all DB-driven rules have been applied.
 * Grants any authenticated user (via the 'member' base role) permission to
 * update their own user record. The OwnershipAssertion compares
 * ProprietaryInterface::getOwnerId() on both the role and the resource —
 * the check passes only when the current user is editing their own record.
 *
 * Admin-level roles receive update access via the DB-driven admin.user rules
 * and do not need the assertion — their grant is unconditional.
 */
final class RegisterOwnershipAssertionListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        $event->acl->allow('member', 'user', 'update', new OwnershipAssertion());
    }
}
