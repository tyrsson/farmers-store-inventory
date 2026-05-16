<?php

declare(strict_types=1);

namespace Webware\Acl;

use Laminas\Permissions\Acl\AclInterface as LaminasAclInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\RouteResult;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\Http\RouteResource;

final class Acl implements AclInterface
{
    public function __construct(
        private readonly LaminasAclInterface $acl,
        private readonly array $paramMap = [],
    ) {}

    public function getAcl(): LaminasAclInterface
    {
        return $this->acl;
    }

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

        // Not opted in as a resource — allow through (opt-in model)
        if (! $this->acl->hasResource($routeName)) {
            return true;
        }

        $routeResource = new RouteResource($routeResult, $request, $this->paramMap);

        return $this->isAllowed($roles, $routeResource, $routeResource->getPrivilegeId());
    }

    #[Override]
    public function isAllowedByRouteName(
        string $routeName,
        array|RoleInterface|string|null $roles = null,
    ): bool {
        // Not opted in = not protected = allow
        if (! $this->acl->hasResource($routeName)) {
            return true;
        }

        // null privilege = "any" — correct for navigation visibility checks
        return $this->isAllowed($roles, $routeName, null);
    }
}
