<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\CommandBus\CommandBusInterface;

final class ProcessResourceMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessResourceMiddleware
    {
        return new ProcessResourceMiddleware(
            $container->get(CommandBusInterface::class),
        );
    }
}
