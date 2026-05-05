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

namespace User;

use Htmx\Middleware\DisableBodyMiddleware;
use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use User\Admin\RequestHandler\CreateUserHandler;
use User\Admin\RequestHandler\ToggleUserActiveHandler;
use User\Admin\RequestHandler\UpdateUserHandler;
use User\Middleware\RegistrationMiddleware;
use User\RequestHandler\LoginHandler;
use User\RequestHandler\LogoutHandler;
use User\RequestHandler\RegistrationHandler;
use User\RequestHandler\ResendVerificationHandler;
use User\RequestHandler\UserListHandler;
use User\RequestHandler\VerifyEmailHandler;
use Webware\Acl\Acl\AuthorizationMiddleware;

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
        );

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
        );

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
        );

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
