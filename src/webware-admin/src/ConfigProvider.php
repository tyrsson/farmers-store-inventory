<?php

declare(strict_types=1);

namespace Webware\Admin;

use Webware\Admin\Container\DashboardHandlerFactory;
use Webware\Admin\Container\DashboardMiddlewareFactory;
use Webware\Admin\Middleware\DashboardMiddleware;
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
                DashboardMiddleware::class => DashboardMiddlewareFactory::class,
            ],
        ];
    }
}
