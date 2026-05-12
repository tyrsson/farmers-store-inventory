<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessAssertionMiddleware;
use Webware\CommandBus\CommandBusInterface;

final class ProcessAssertionMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessAssertionMiddleware
    {
        return new ProcessAssertionMiddleware(
            $container->get(CommandBusInterface::class),
        );
    }
}
