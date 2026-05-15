<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\ResourceListHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class ResourceListHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResourceListHandler
    {
        return new ResourceListHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(TemplateRendererInterface::class),
            $container->get(RouteCollectorInterface::class),
        );
    }
}
