<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\CommandHandler\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\CommandHandler\ProtectRouteHandler;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\Repository\AclRepositoryInterface;

final class ProtectRouteHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProtectRouteHandler
    {
        return new ProtectRouteHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(AclCacheInterface::class),
        );
    }
}
