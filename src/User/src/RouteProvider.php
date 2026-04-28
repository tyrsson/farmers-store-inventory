<?php

declare(strict_types=1);

namespace User;

use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use User\Admin\RequestHandler\CreateUserHandler;
use User\Admin\RequestHandler\ToggleUserActiveHandler;
use User\Admin\RequestHandler\UpdateUserHandler;
use User\RequestHandler\LoginHandler;
use User\RequestHandler\LogoutHandler;
use User\RequestHandler\UserListHandler;

final class RouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        // Login routes — no AuthenticationMiddleware (would cause redirect loop)
        $routeCollector->get(
            '/login',
            $middlewareFactory->prepare([LoginHandler::class]),
            'user.login'
        );

        $routeCollector->post(
            '/login',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, LoginHandler::class]),
            'user.login.post'
        );

        // All routes below require authentication
        $routeCollector->get(
            '/logout',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, LogoutHandler::class]),
            'user.logout'
        );

        // Admin
        $routeCollector->get(
            '/admin/user',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, UserListHandler::class]),
            'admin.user.list'
        );

        $routeCollector->route(
            '/admin/create/user',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, CreateUserHandler::class]),
            ['GET', 'POST'],
            'admin.create.user'
        );

        $routeCollector->route(
            '/admin/update/user/{id:\d+}',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, UpdateUserHandler::class]),
            ['GET', 'POST'],
            'admin.update.user'
        );

        $routeCollector->post(
            '/admin/toggle/user/{id:\d+}',
            $middlewareFactory->prepare([AuthenticationMiddleware::class, ToggleUserActiveHandler::class]),
            'admin.toggle.user'
        );
    }
}
