<?php

declare(strict_types=1);

namespace Webware\Acl;

use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AclInterface
{
    /**
     * @param string[]|RoleInterface[]|string|null $roles
     * @param string|ResourceInterface|null $resource
     * @param null|string $privilege
     */
    public function isAllowed(
        array|RoleInterface|string|null $roles = null,
        string|ResourceInterface|null $resource = null,
        ?string $privilege = null,
    ): bool;

    /**
     * @param string[]|RoleInterface[]|string|null $roles
     */
    public function isAllowedRoute(
        ServerRequestInterface $request,
        array|RoleInterface|string|null $roles = null,
    ): bool;

    /**
     * ACL check by route name without a request object.
     * Returns true when the route name has no mapping (not ACL-protected).
     *
     * @param string[]|RoleInterface[]|string|null $roles
     */
    public function isAllowedByRouteName(
        string $routeName,
        array|RoleInterface|string|null $roles = null,
    ): bool;
}
