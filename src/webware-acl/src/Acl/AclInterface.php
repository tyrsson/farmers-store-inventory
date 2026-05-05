<?php

declare(strict_types=1);

namespace Webware\Acl\Acl;

use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AclInterface
{
    /**
     * 
     * @param string[]|RoleInterface[]|string|null $roles 
     * @param string|ResourceInterface|null $resource 
     * @param null|string $privilege 
     * @return bool 
     */
    public function isAllowed(
        array|RoleInterface|string|null $roles = null,
        string|ResourceInterface|null $resource = null,
        ?string $privilege = null,
    ): bool;
    
    /**
     * 
     * @param ServerRequestInterface $request 
     * @param string[]|RoleInterface[]|string|null $roles 
     * @return bool 
     */
    public function isAllowedRoute(
        ServerRequestInterface $request,
        array|RoleInterface|string|null $roles = null,
    ): bool;
}
