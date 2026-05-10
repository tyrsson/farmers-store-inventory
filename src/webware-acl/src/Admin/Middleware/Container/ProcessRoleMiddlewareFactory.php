<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Repository\AclRepositoryInterface;

final class ProcessRoleMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessRoleMiddleware
    {
        return new ProcessRoleMiddleware(
            $container->get(AclRepositoryInterface::class),
        );
    }
}
