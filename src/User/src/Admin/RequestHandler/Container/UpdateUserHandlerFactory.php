<?php

declare(strict_types=1);

namespace User\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use User\Admin\RequestHandler\UpdateUserHandler;
use User\Repository\UserRepositoryInterface;

final class UpdateUserHandlerFactory
{
    public function __invoke(ContainerInterface $container): UpdateUserHandler
    {
        return new UpdateUserHandler(
            template: $container->get(TemplateRendererInterface::class),
            users:    $container->get(UserRepositoryInterface::class),
        );
    }
}
