<?php

declare(strict_types=1);

namespace Webware\Admin\Container;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Acl\AclInterface;
use Webware\Admin\Middleware\CollectDashboardWidgetsMiddleware;

final class CollectDashboardWidgetsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CollectDashboardWidgetsMiddleware
    {
        return new CollectDashboardWidgetsMiddleware(
            dispatcher: $container->get(EventDispatcherInterface::class),
            acl:        $container->get(AclInterface::class),
        );
    }
}
