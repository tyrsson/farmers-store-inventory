<?php

declare(strict_types=1);

namespace Ims\Store\Acl;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Override;

final class StoreOwnedResourceAssertion implements AssertionInterface
{
    #[Override]
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
}
