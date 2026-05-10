<?php

declare(strict_types=1);


namespace Webware\Acl;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Override;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Admin\RequestHandler\ResourceListHandler;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Admin\RequestHandler\RouteMapManagerHandler;
use Webware\Acl\Admin\RequestHandler\RuleManagerHandler;
use Webware\Acl\Middleware\AuthorizationMiddleware;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Webware\Acl\Admin\Middleware\ProcessAssertionMiddleware;
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRouteMappingMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;

final readonly class RouteProvider implements RouteProviderInterface
{
    #[Override]
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory
    ): void {
        // ACL overview — GET summary dashboard
        $routeCollector->get(
            '/admin/access',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                AclOverviewHandler::class,
            ]),
            'admin.acl.read'
        )->setOptions([
            'navigation' => 'admin',
            'label'      => 'ACL Manager',
            'icon'       => 'bi-shield-lock',
            'parent'     => null,
            'order'      => 15,
        ]);

        // Route map management — GET list
        $routeCollector->get(
            '/admin/access/routes',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                RouteMapManagerHandler::class,
            ]),
            'admin.acl.routes.read'
        )->setOptions([
            'label'  => 'Route Map',
            'icon'   => 'bi-signpost-split-fill',
            'parent' => 'admin.acl.read',
            'order'  => 20,
        ]);

        // Role management — GET list
        $routeCollector->get(
            '/admin/access/roles',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                RoleListHandler::class,
            ]),
            'admin.acl.roles.read'
        )->setOptions([
            'label'  => 'Roles',
            'icon'   => 'bi-shield-lock-fill',
            'parent' => 'admin.acl.read',
            'order'  => 30,
        ]);

        // Resource management — GET list
        $routeCollector->get(
            '/admin/access/resources',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ResourceListHandler::class,
            ]),
            'admin.acl.resources.read'
        )->setOptions([
            'label'  => 'Resources',
            'icon'   => 'bi-file-earmark-lock-fill',
            'parent' => 'admin.acl.read',
            'order'  => 40,
        ]);

        // Rule management — GET matrix
        $routeCollector->get(
            '/admin/access/rules',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.rules.read'
        )->setOptions([
            'label'  => 'Rules',
            'icon'   => 'bi-list-check',
            'parent' => 'admin.acl.read',
            'order'  => 50,
        ]);

        // Rules write/delete
        $routeCollector->post(
            '/admin/access/rules',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRuleMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.rules.create'
        );

        $routeCollector->patch(
            '/admin/access/rules/{id:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                BodyParamsMiddleware::class,
                ProcessRuleMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.rules.update'
        );

        $routeCollector->delete(
            '/admin/access/rules/{id:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRuleMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.rules.delete'
        );

        // Assertion management — POST add, DELETE remove
        $routeCollector->post(
            '/admin/access/rules/{rule_id:\d+}/assertions',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                BodyParamsMiddleware::class,
                ProcessAssertionMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.assertions.create'
        );

        $routeCollector->delete(
            '/admin/access/rules/{rule_id:\d+}/assertions/{id:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessAssertionMiddleware::class,
                RuleManagerHandler::class,
            ]),
            'admin.acl.assertions.delete'
        );

        // Route mappings write/delete
        $routeCollector->post(
            '/admin/access/routes',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRouteMappingMiddleware::class,
                RouteMapManagerHandler::class,
            ]),
            'admin.acl.routes.create'
        );

        $routeCollector->delete(
            '/admin/access/routes/{route_name:[a-z0-9._-]+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRouteMappingMiddleware::class,
                RouteMapManagerHandler::class,
            ]),
            'admin.acl.routes.delete'
        );

        // Roles write
        $routeCollector->post(
            '/admin/access/roles',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRoleMiddleware::class,
                RoleListHandler::class,
            ]),
            'admin.acl.roles.create'
        );

        // Roles delete
        $routeCollector->delete(
            '/admin/access/roles/{pk:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessRoleMiddleware::class,
                RoleListHandler::class,
            ]),
            'admin.acl.roles.delete'
        );

        // Resources write
        $routeCollector->post(
            '/admin/access/resources',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessResourceMiddleware::class,
                ResourceListHandler::class,
            ]),
            'admin.acl.resources.create'
        );

        // Resources delete
        $routeCollector->delete(
            '/admin/access/resources/{pk:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessResourceMiddleware::class,
                ResourceListHandler::class,
            ]),
            'admin.acl.resources.delete'
        );

    }
}
