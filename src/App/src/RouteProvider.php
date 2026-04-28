<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Mezzio Bleeding Edge package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\Handler\PingHandler;
use App\RequestHandler\DashboardHandler;
use Mezzio\Authentication\AuthenticationMiddleware;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;

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
                    AuthenticationMiddleware::class,
                    DashboardHandler::class,
                ]
            ),
            'dashboard'
        );

        $routeCollector->get(
            '/ping',
            $middlewareFactory->prepare(
                PingHandler::class
            ),
            'api.ping'
        );
    }
}
