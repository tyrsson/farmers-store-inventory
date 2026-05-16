<?php

declare(strict_types=1);

namespace Webware\Acl\Http;

use Ims\Store\Acl\StoreOwnedResourceInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Webware\Acl\PrivilegeInterface;
use Webware\Acl\RoleProviderInterface;
use Webware\UserManager\UserInterface;

/**
 * Bridges a Mezzio RouteResult into a Laminas ACL resource.
 *
 * Resource ID  = matched route name
 * Privilege    = HTTP method mapped to create/read/update/delete
 * Role         = authenticated UserInterface from request attribute
 * OwnerId      = resolved from route param → query string → request attribute
 *                using three-level config: per-route options > global param map > convention
 *
 * @note StoreOwnedResourceInterface creates a dependency on ims-store. When extracting
 *       webware-acl as a standalone ecosystem package, move the interface or provide
 *       a webware-acl-store bridge package.
 */
final class RouteResource implements
    ResourceInterface,
    RoleProviderInterface,
    PrivilegeInterface,
    StoreOwnedResourceInterface
{
    private const array METHOD_PRIVILEGE_MAP = [
        'GET'    => PrivilegeInterface::READ,
        'POST'   => PrivilegeInterface::CREATE,
        'PUT'    => PrivilegeInterface::UPDATE,
        'PATCH'  => PrivilegeInterface::UPDATE,
        'DELETE' => PrivilegeInterface::DELETE,
    ];

    public function __construct(
        private readonly RouteResult $routeResult,
        private readonly ServerRequestInterface $request,
        private readonly array $paramMap = [],
    ) {}

    public function getResourceId(): string
    {
        return $this->routeResult->getMatchedRouteName();
    }

    public function getPrivilegeId(): string
    {
        return self::METHOD_PRIVILEGE_MAP[$this->request->getMethod()]
            ?? PrivilegeInterface::READ;
    }

    public function getRole(): RoleInterface
    {
        return $this->request->getAttribute(UserInterface::class);
    }

    public function getOwnerId(): int
    {
        $routeName = $this->getResourceId();

        // 1. Per-route options array (most specific)
        $routeOptions = $this->routeResult->getMatchedRoute()->getOptions();
        $paramName    = $routeOptions['acl']['ownerId']
            // 2. Global route_param_map config (app-level fallback)
            ?? $this->paramMap[$routeName]['ownerId']
            // 3. Convention
            ?? 'ownerId';

        return (int) (
            $this->routeResult->getMatchedParams()[$paramName]
            ?? $this->request->getQueryParams()[$paramName]
            ?? $this->request->getAttribute($paramName)
            ?? 0
        );
    }
}
