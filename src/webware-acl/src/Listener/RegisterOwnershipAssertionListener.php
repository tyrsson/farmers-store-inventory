<?php

declare(strict_types=1);


namespace Webware\Acl\Listener;

use Ims\Store\Acl\StoreOwnedResourceAssertion;
use Webware\Acl\Event\AclBuiltEvent;

/**
 * Registers runtime allow/deny rules for store-scoped resources that require
 * StoreOwnedResourceAssertion. These rules cannot be purely DB-driven because
 * the assertion must be injected as a PHP object at rule-creation time.
 *
 * Fired on AclBuiltEvent (after AclBuilder completes) at priority 1.
 *
 * ## Store-scoped mutation rules
 *
 * - member (and descendants): create + update on store resources, gated by assertion
 * - Warehouse Supervisor (and above): delete on store resources, gated by assertion
 * - DC Warehouse: explicit deny for create + update + delete (read-only cross-store role)
 * - Manager (and above): update on store.settings, gated by assertion
 * - Administrator: unrestricted create + update + delete on all store resources
 *   (own explicit allow with no assertion overrides inherited asserted member rule)
 *
 * Read grants and global-resource grants are seeded as plain SQL rows in 999_seed.sql
 * because they require no assertion.
 */
final class RegisterOwnershipAssertionListener
{
    /** Resources governed by store ownership boundaries */
    private const array STORE_RESOURCES = [
        'manifest',
        'product',
        'product_image',
        'ticket',
        'transfer',
    ];

    public function __invoke(AclBuiltEvent $event): void
    {
        $acl       = $event->acl;
        $assertion = new StoreOwnedResourceAssertion();

        // member (→ Warehouse → Warehouse Supervisor → …): own-store create + update only
        $acl->allow('member', self::STORE_RESOURCES, ['create', 'update'], $assertion);

        // Warehouse Supervisor (→ DC Warehouse → Manager → Administrator → Developer):
        // own-store delete
        $acl->allow('Warehouse Supervisor', self::STORE_RESOURCES, ['delete'], $assertion);

        // DC Warehouse: read-only cross-store role — block all inherited mutation grants
        $acl->deny('DC Warehouse', self::STORE_RESOURCES, ['create', 'update', 'delete']);

        // Manager (→ Administrator → Developer): own store settings only
        $acl->allow('Manager', ['store.settings'], ['update'], $assertion);

        // Administrator: unrestricted by store boundary — own explicit rule overrides
        // the inherited member assertion (empirically verified, May 13 2026)
        $acl->allow('Administrator', array_merge(self::STORE_RESOURCES, ['store.settings']), ['create', 'update', 'delete']);
    }
}
