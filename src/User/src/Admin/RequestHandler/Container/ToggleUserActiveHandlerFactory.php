<?php

declare(strict_types=1);

namespace User\Admin\RequestHandler\Container;

use Psr\Container\ContainerInterface;
use User\Admin\RequestHandler\ToggleUserActiveHandler;
use User\Repository\UserRepositoryInterface;

final class ToggleUserActiveHandlerFactory
{
    public function __invoke(ContainerInterface $container): ToggleUserActiveHandler
    {
        return new ToggleUserActiveHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
