<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
