<?php

declare(strict_types=1);

namespace Webware\Acl\Acl;

use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\RouteResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;

final class Acl implements AclInterface
{
    /**
     * @param array<string, array{resource_id: string, privilege_id: string}> $routeMappings
     */
    public function __construct(
        private readonly LaminasAclInterface $acl,
        private readonly array $routeMappings = [],
    ) {}

    #[Override]
    public function isAllowed(
        array|RoleInterface|string|null $roles = null,
        string|ResourceInterface|null $resource = null,
        ?string $privilege = null
    ): bool {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        foreach ($roles as $role) {
            if ($role instanceof RoleInterface) {
                $role = $role->getRoleId();
            }
            if ($this->acl->isAllowed($role, $resource, $privilege)) {
                return true;
            }
        }
        return false;
    }

    #[Override]
    public function isAllowedRoute(
        ServerRequestInterface $request,
        array|RoleInterface|string|null $roles = null,
    ): bool {
        $routeResult = $request->getAttribute(RouteResult::class);

        if (! ($routeResult instanceof RouteResult) || $routeResult->isFailure()) {
            return true;
        }

        $routeName = $routeResult->getMatchedRouteName();

        if (! isset($this->routeMappings[$routeName])) {
            return false;
        }

        $mapping = $this->routeMappings[$routeName];

        return $this->isAllowed($roles, $mapping['resource_id'], $mapping['privilege_id']);
    }

}
