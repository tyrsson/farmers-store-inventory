<?php

declare(strict_types=1);

namespace Webware\Admin;

use Webware\Admin\Container\CollectDashboardWidgetsMiddlewareFactory;
use Webware\Admin\Container\DashboardHandlerFactory;
use Webware\Admin\Middleware\CollectDashboardWidgetsMiddleware;
use Webware\Admin\RequestHandler\DashboardHandler;

final readonly class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                DashboardHandler::class                    => DashboardHandlerFactory::class,
                CollectDashboardWidgetsMiddleware::class   => CollectDashboardWidgetsMiddlewareFactory::class,
            ],
        ];
    }
}
