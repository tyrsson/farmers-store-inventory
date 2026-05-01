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

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use User\Admin\RequestHandler\CreateUserHandler;

final class CreateUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): CreateUserHandler
    {
        return new CreateUserHandler(
            template: $container->get(TemplateRendererInterface::class),
        );
    }
}
