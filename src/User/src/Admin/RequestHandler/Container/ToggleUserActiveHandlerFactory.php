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
