<?php

declare(strict_types=1);

namespace User\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use User\CommandHandler\SaveUserHandler;
use User\Repository\UserRepositoryInterface;

final class SaveUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): SaveUserHandler
    {
        return new SaveUserHandler(
            users: $container->get(UserRepositoryInterface::class),
        );
    }
}
