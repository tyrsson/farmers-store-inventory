<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppTest\Acl;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Webware\Acl\Assertion\OwnershipAssertion;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\GenericResource;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\GenericRole;
use Laminas\Permissions\Acl\Role\RoleInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Prototype integration test — verifies that Laminas ACL + AssertionAggregate
 * can enforce both user-profile ownership and store-scoped ownership using only
 * Laminas primitives.
 *
 * Key design proven here:
 *   - User::getOwnerId() returns the user's PK (profile ownership)
 *   - Store scope is read via getDetail('store_id') in a separate assertion
 *   - Two distinct assertions are needed; AssertionAggregate can stack them
 *
 * All collaborators are anonymous classes — no dependency on application entities.
 */
final class StoreOwnershipAssertionPrototypeTest extends TestCase
{
    private Acl $acl;

    // ── User: PK=1, storeId=42 ───────────────────────────────────────────────
    private RoleInterface&ProprietaryInterface $memberUser;

    // ── Another user: PK=2, storeId=99 ──────────────────────────────────────
    private RoleInterface&ProprietaryInterface $otherUser;

    // ── User profile resources ───────────────────────────────────────────────
    private ResourceInterface&ProprietaryInterface $ownProfile;
    private ResourceInterface&ProprietaryInterface $otherProfile;

    // ── Manifest resources ───────────────────────────────────────────────────
    private ResourceInterface&ProprietaryInterface $ownManifest;
    private ResourceInterface&ProprietaryInterface $foreignManifest;

    protected function setUp(): void
    {
        $this->acl = new Acl();

        $this->acl->addRole(new GenericRole('member'));
        $this->acl->addResource(new GenericResource('user.profile'));
        $this->acl->addResource(new GenericResource('store.manifest'));

        // ── Member user: userId=1, storeId=42 ────────────────────────────────
        // getOwnerId() returns userId (PK) — used for profile ownership.
        // getDetail('store_id') returns storeId — used for store-scope assertion.
        $this->memberUser = new class (1, 42) implements RoleInterface, ProprietaryInterface
        {
            public function __construct(
                private readonly int $userId,
                private readonly int $storeId,
            ) {}

            public function getRoleId(): string { return 'member'; }

            public function getOwnerId(): int { return $this->userId; }

            public function getDetail(string $name, mixed $default = null): mixed
            {
                return match ($name) {
                    'store_id' => $this->storeId,
                    default    => $default,
                };
            }
        };

        // ── Other user: userId=2, storeId=99 ─────────────────────────────────
        $this->otherUser = new class (2, 99) implements RoleInterface, ProprietaryInterface
        {
            public function __construct(
                private readonly int $userId,
                private readonly int $storeId,
            ) {}

            public function getRoleId(): string { return 'member'; }

            public function getOwnerId(): int { return $this->userId; }

            public function getDetail(string $name, mixed $default = null): mixed
            {
                return match ($name) {
                    'store_id' => $this->storeId,
                    default    => $default,
                };
            }
        };

        // ── Own profile: owned by userId=1 ───────────────────────────────────
        $this->ownProfile = new class (1) implements ResourceInterface, ProprietaryInterface
        {
            public function __construct(private readonly int $ownerId) {}
            public function getResourceId(): string { return 'user.profile'; }
            public function getOwnerId(): int { return $this->ownerId; }
        };

        // ── Other user's profile: owned by userId=2 ──────────────────────────
        $this->otherProfile = new class (2) implements ResourceInterface, ProprietaryInterface
        {
            public function __construct(private readonly int $ownerId) {}
            public function getResourceId(): string { return 'user.profile'; }
            public function getOwnerId(): int { return $this->ownerId; }
        };

        // ── Own manifest: owned by storeId=42 ────────────────────────────────
        $this->ownManifest = new class (42) implements ResourceInterface, ProprietaryInterface
        {
            public function __construct(private readonly int $storeId) {}
            public function getResourceId(): string { return 'store.manifest'; }
            public function getOwnerId(): int { return $this->storeId; }
        };

        // ── Foreign manifest: owned by storeId=99 ────────────────────────────
        $this->foreignManifest = new class (99) implements ResourceInterface, ProprietaryInterface
        {
            public function __construct(private readonly int $storeId) {}
            public function getResourceId(): string { return 'store.manifest'; }
            public function getOwnerId(): int { return $this->storeId; }
        };
    }

    /**
     * Profile ownership: Webware\Acl\Assertion\OwnershipAssertion — fail-closed.
     * Denies if either side lacks ProprietaryInterface or resource owner is null.
     */
    private function buildProfileOwnershipAssertion(): OwnershipAssertion
    {
        return new OwnershipAssertion();
    }

    /**
     * Store ownership: compares user's store_id (via getDetail) against resource's storeId (getOwnerId).
     * This will become StoreOwnershipAssertion in ims-store.
     */
    private function buildStoreOwnershipAssertion(): AssertionInterface
    {
        return new class implements AssertionInterface
        {
            public function assert(
                Acl $acl,
                ?RoleInterface $role = null,
                ?ResourceInterface $resource = null,
                $privilege = null,
            ): bool {
                if (! $resource instanceof ProprietaryInterface) {
                    return false;
                }

                if (! method_exists($role, 'getDetail')) {
                    return false;
                }

                return (int) $role->getDetail('store_id') === (int) $resource->getOwnerId();
            }
        };
    }

    private function buildAggregate(AssertionInterface $assertion): AssertionAggregate
    {
        $aggregate = new AssertionAggregate();
        $aggregate->addAssertion($assertion);

        return $aggregate;
    }

    // ── Profile ownership tests ───────────────────────────────────────────────

    #[Test]
    public function memberCanEditOwnProfile(): void
    {
        $this->acl->allow('member', 'user.profile', 'edit', $this->buildProfileOwnershipAssertion());

        self::assertTrue(
            $this->acl->isAllowed($this->memberUser, $this->ownProfile, 'edit'),
        );
    }

    #[Test]
    public function memberCannotEditOtherUsersProfile(): void
    {
        $this->acl->allow('member', 'user.profile', 'edit', $this->buildProfileOwnershipAssertion());

        self::assertFalse(
            $this->acl->isAllowed($this->memberUser, $this->otherProfile, 'edit'),
        );
    }

    #[Test]
    public function aggregateEnforcesProfileOwnership(): void
    {
        $this->acl->allow('member', 'user.profile', 'edit', $this->buildAggregate($this->buildProfileOwnershipAssertion()));

        self::assertTrue($this->acl->isAllowed($this->memberUser, $this->ownProfile, 'edit'));
        self::assertFalse($this->acl->isAllowed($this->memberUser, $this->otherProfile, 'edit'));
    }

    // ── Store ownership tests ─────────────────────────────────────────────────

    #[Test]
    public function memberCanSaveOwnStoreManifest(): void
    {
        $this->acl->allow('member', 'store.manifest', 'save', $this->buildStoreOwnershipAssertion());

        self::assertTrue(
            $this->acl->isAllowed($this->memberUser, $this->ownManifest, 'save'),
        );
    }

    #[Test]
    public function memberCannotSaveForeignStoreManifest(): void
    {
        $this->acl->allow('member', 'store.manifest', 'save', $this->buildStoreOwnershipAssertion());

        self::assertFalse(
            $this->acl->isAllowed($this->memberUser, $this->foreignManifest, 'save'),
        );
    }

    #[Test]
    public function aggregateEnforcesStoreOwnership(): void
    {
        $this->acl->allow('member', 'store.manifest', 'save', $this->buildAggregate($this->buildStoreOwnershipAssertion()));

        self::assertTrue($this->acl->isAllowed($this->memberUser, $this->ownManifest, 'save'));
        self::assertFalse($this->acl->isAllowed($this->memberUser, $this->foreignManifest, 'save'));
    }
}
