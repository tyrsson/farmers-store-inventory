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

namespace Webware\UserManager;

use Htmx\Middleware\DisableBodyMiddleware;
use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Webware\UserManager\Admin\RequestHandler\CreateUserHandler;
use Webware\UserManager\Admin\RequestHandler\ToggleUserActiveHandler;
use Webware\UserManager\Admin\RequestHandler\UpdateUserHandler;
use Webware\UserManager\Middleware\RegistrationMiddleware;
use Webware\UserManager\RequestHandler\LoginHandler;
use Webware\UserManager\RequestHandler\LogoutHandler;
use Webware\UserManager\RequestHandler\RegistrationHandler;
use Webware\UserManager\RequestHandler\ResendVerificationHandler;
use Webware\UserManager\RequestHandler\UserListHandler;
use Webware\UserManager\RequestHandler\VerifyEmailHandler;
use Webware\Acl\Middleware\AuthorizationMiddleware;

final class RouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        // Login routes — AclMiddleware runs before Auth (login/register are guest grants)
        $routeCollector->get(
            '/login',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    LoginHandler::class,
                ]
            ),
            'user.login'
        );

        $routeCollector->post(
            '/login',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    AuthenticationMiddleware::class,
                    LoginHandler::class,
                ]
            ),
            'user.login.post'
        );

        // Registration routes
        $routeCollector->get(
            '/register',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    RegistrationHandler::class,
                ]
            ),
            'user.register'
        );

        $routeCollector->post(
            '/register',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    RegistrationMiddleware::class,
                    RegistrationHandler::class,
                ]
            ),
            'user.register.post'
        );

        $routeCollector->get(
            '/verify-email/{token}',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    VerifyEmailHandler::class,
                ]
            ),
            'user.verify-email'
        );

        $routeCollector->route(
            '/resend-verification',
            $middlewareFactory->prepare(
                [
                    DisableBodyMiddleware::class,
                    AuthorizationMiddleware::class,
                    ResendVerificationHandler::class,
                ]
            ),
            ['GET', 'POST'],
            'user.verify-email.resend'
        );

        $routeCollector->get(
            '/logout',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    LogoutHandler::class,
                ]
            ),
            'user.logout'
        )->setOptions([
            'navigation' => 'user',
            'label'      => 'Logout',
            'icon'       => 'bi-box-arrow-right',
            'parent'     => null,
            'order'      => 10,
        ]);

        // Admin
        $routeCollector->get(
            '/admin/user',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    UserListHandler::class,
                ]
            ),
            'admin.user.list'
        )->setOptions([
            'navigation' => 'admin',
            'label'      => 'Users',
            'icon'       => 'bi-people-fill',
            'parent'     => null,
            'order'      => 20,
        ]);

        $routeCollector->route(
            '/admin/create/user',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    CreateUserHandler::class,
                ]
            ),
            ['GET', 'POST'],
            'admin.create.user'
        )->setOptions([
            'navigation' => 'admin',
            'label'      => 'Create User',
            'icon'       => 'bi-person-plus-fill',
            'parent'     => 'admin.user.list',
            'order'      => 10,
        ]);

        $routeCollector->route(
            '/admin/update/user/{id:\d+}',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    UpdateUserHandler::class,
                ]
            ),
            ['GET', 'POST'],
            'admin.update.user'
        );

        $routeCollector->post(
            '/admin/toggle/user/{id:\d+}',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    ToggleUserActiveHandler::class,
                ]
            ),
            'admin.toggle.user'
        );
    }
}
