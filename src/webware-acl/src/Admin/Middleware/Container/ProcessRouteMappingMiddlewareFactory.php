<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessRouteMappingMiddleware;
use Webware\CommandBus\CommandBusInterface;

final class ProcessRouteMappingMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessRouteMappingMiddleware
    {
        return new ProcessRouteMappingMiddleware(
            $container->get(CommandBusInterface::class),
        );
    }
}
