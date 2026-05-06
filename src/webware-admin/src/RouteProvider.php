<?php

declare(strict_types=1);

namespace Webware\Admin;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Override;

final readonly class RouteProvider implements RouteProviderInterface
{
    #[Override]
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory
    ): void {
            $routeCollector->get(
                '/admin',
                $middlewareFactory->prepare(
                    [
                        RequestHandler\DashboardHandler::class,
                    ]
                ),
                'admin.dashboard'
            );
    }
}
