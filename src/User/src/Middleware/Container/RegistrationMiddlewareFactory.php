<?php

declare(strict_types=1);

namespace User\Middleware\Container;

use CuyZ\Valinor\Mapper\TreeMapper;
use Psr\Container\ContainerInterface;
use User\Middleware\RegistrationMiddleware;
use Webware\CommandBus\CommandBusInterface;

final class RegistrationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): RegistrationMiddleware
    {
        return new RegistrationMiddleware(
            $container->get(TreeMapper::class),
            $container->get(CommandBusInterface::class),
        );
    }
}
