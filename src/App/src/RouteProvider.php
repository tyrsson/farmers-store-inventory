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

namespace App;

use App\RequestHandler\DashboardHandler;
use App\RequestHandler\PingHandler;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Webware\Acl\Acl\AuthorizationMiddleware;

final class RouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        $routeCollector->get(
            '/',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    DashboardHandler::class,
                ]
            ),
            'dashboard'
        );

        $routeCollector->get(
            '/ping',
            $middlewareFactory->prepare(
                [
                    AuthorizationMiddleware::class,
                    PingHandler::class,
                ]
            ),
            'api.ping'
        );
    }
}
