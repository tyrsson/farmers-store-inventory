<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclBuilder;
use Webware\Acl\Admin\RequestHandler\RouteMapManagerHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class RouteMapManagerHandlerFactory
{
    public function __invoke(ContainerInterface $container): RouteMapManagerHandler
    {
        return new RouteMapManagerHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(TemplateRendererInterface::class),
            $container->get(RouteCollectorInterface::class),
            $container->get(AclBuilder::class),
        );
    }
}
