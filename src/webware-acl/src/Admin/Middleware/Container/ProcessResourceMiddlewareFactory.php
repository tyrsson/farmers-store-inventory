<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\Acl\Repository\AclRepositoryInterface;

final class ProcessResourceMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessResourceMiddleware
    {
        return new ProcessResourceMiddleware(
            $container->get(AclRepositoryInterface::class),
        );
    }
}
