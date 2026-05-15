<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Middleware\AuthorizingDispatchMiddleware;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;

final class AuthorizingDispatchMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthorizingDispatchMiddleware
    {
        return new AuthorizingDispatchMiddleware(
            $container->get(AclInterface::class),
            $container->get(ForbiddenHandlerInterface::class),
        );
    }
}
