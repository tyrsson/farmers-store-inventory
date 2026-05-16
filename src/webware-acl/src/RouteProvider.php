<?php

declare(strict_types=1);


namespace Webware\Acl;

use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Override;
use Webware\Acl\Admin\Middleware\ProcessAssertionMiddleware;
use Webware\Acl\Admin\Middleware\ProcessProtectRouteMiddleware;
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Admin\RequestHandler\ResourceListHandler;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Admin\RequestHandler\RuleManagerHandler;

final readonly class RouteProvider implements RouteProviderInterface
{
    #[Override]
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory
    ): void {
        // ACL overview
        $routeCollector->get(
            '/admin/access',
            $middlewareFactory->prepare([AclOverviewHandler::class]),
            'admin.acl.read'
        )->setOptions([
            'navigation' => 'admin',
            'label'      => 'ACL Manager',
            'icon'       => 'bi-shield-lock',
            'parent'     => null,
            'order'      => 15,
        ]);

        // Role management
        $routeCollector->get(
            '/admin/access/roles',
            $middlewareFactory->prepare([RoleListHandler::class]),
            'admin.acl.roles.read'
        )->setOptions([
            'label'  => 'Roles',
            'icon'   => 'bi-shield-lock-fill',
            'parent' => 'admin.acl.read',
            'order'  => 30,
        ]);

        // Resource management
        $routeCollector->get(
            '/admin/access/resources',
            $middlewareFactory->prepare([ResourceListHandler::class]),
            'admin.acl.resources.read'
        )->setOptions([
            'label'  => 'Resources',
            'icon'   => 'bi-file-earmark-lock-fill',
            'parent' => 'admin.acl.read',
            'order'  => 40,
        ]);

        // Rule management
        $routeCollector->get(
            '/admin/access/rules',
            $middlewareFactory->prepare([RuleManagerHandler::class]),
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
            $middlewareFactory->prepare([ProcessRuleMiddleware::class, RuleManagerHandler::class]),
            'admin.acl.rules.create'
        );
        $routeCollector->patch(
            '/admin/access/rules/{id:\d+}',
            $middlewareFactory->prepare([BodyParamsMiddleware::class, ProcessRuleMiddleware::class, RuleManagerHandler::class]),
            'admin.acl.rules.update'
        );
        $routeCollector->delete(
            '/admin/access/rules/{id:\d+}',
            $middlewareFactory->prepare([ProcessRuleMiddleware::class, RuleManagerHandler::class]),
            'admin.acl.rules.delete'
        );

        // Assertion management
        $routeCollector->post(
            '/admin/access/rules/{rule_id:\d+}/assertions',
            $middlewareFactory->prepare([BodyParamsMiddleware::class, ProcessAssertionMiddleware::class, RuleManagerHandler::class]),
            'admin.acl.assertions.create'
        );
        $routeCollector->delete(
            '/admin/access/rules/{rule_id:\d+}/assertions/{id:\d+}',
            $middlewareFactory->prepare([ProcessAssertionMiddleware::class, RuleManagerHandler::class]),
            'admin.acl.assertions.delete'
        );

        // Roles write/delete
        $routeCollector->post(
            '/admin/access/roles',
            $middlewareFactory->prepare([ProcessRoleMiddleware::class, RoleListHandler::class]),
            'admin.acl.roles.create'
        );
        $routeCollector->delete(
            '/admin/access/roles/{pk:\d+}',
            $middlewareFactory->prepare([ProcessRoleMiddleware::class, RoleListHandler::class]),
            'admin.acl.roles.delete'
        );

        // Resources write/delete
        $routeCollector->post(
            '/admin/access/resources',
            $middlewareFactory->prepare([ProcessResourceMiddleware::class, ResourceListHandler::class]),
            'admin.acl.resources.create'
        );
        $routeCollector->delete(
            '/admin/access/resources/{pk:\d+}',
            $middlewareFactory->prepare([ProcessResourceMiddleware::class, ResourceListHandler::class]),
            'admin.acl.resources.delete'
        );

        // Protect a route — POST registers it as an ACL resource
        $routeCollector->post(
            '/admin/access/resources/protect',
            $middlewareFactory->prepare([
                BodyParamsMiddleware::class,
                ProcessProtectRouteMiddleware::class,
                ResourceListHandler::class,
            ]),
            'admin.acl.resources.protect'
        );
    }
}
