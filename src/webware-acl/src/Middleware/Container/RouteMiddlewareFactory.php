<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware\Container;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Middleware\RouteMiddleware;

final class RouteMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): RouteMiddleware
    {
        $paramMap = $container->get('config')[AclInterface::class]['route_param_map'] ?? [];

        return new RouteMiddleware(
            $container->get(RouterInterface::class),
            $paramMap,
        );
    }
}
