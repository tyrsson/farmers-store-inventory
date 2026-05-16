<?php

declare(strict_types=1);

namespace Webware\Admin;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Override;
use Webware\Admin\Middleware\DashboardMiddleware;

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
                        DashboardMiddleware::class,
                        RequestHandler\DashboardHandler::class,
                    ]
                ),
                'admin.dashboard.read'
            )->setOptions([
                'navigation' => 'admin',
                'label'      => 'Dashboard',
                'icon'       => 'bi-grid-fill',
                'parent'     => null,
                'order'      => 10,
            ]);
    }
}
