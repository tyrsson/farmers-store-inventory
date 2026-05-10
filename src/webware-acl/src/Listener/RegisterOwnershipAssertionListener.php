<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Webware\Acl\Event\AclBuiltEvent;

/**
 * Previously registered the OwnershipAssertion for the member → user → update
 * rule inline. The assertion is now stored in the acl_rule_assertion table and
 * attached by AclBuilder when it loads rules from the DB/cache.
 *
 * This listener is retained as a no-op so that existing event-listener wiring
 * in ConfigProvider does not need to be touched. It may be removed entirely
 * once the configuration reference is cleaned up.
 *
 * @deprecated No-op — assertion is now DB-driven via acl_rule_assertion.
 */
final class RegisterOwnershipAssertionListener
{
    public function __invoke(AclBuiltEvent $event): void
    {
        // No-op: the OwnershipAssertion for member/user/update is now stored in
        // acl_rule_assertion and attached by AclBuilder::buildAssertion().
    }
}
