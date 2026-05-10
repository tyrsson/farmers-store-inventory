<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class RoleListHandlerFactory
{
    public function __invoke(ContainerInterface $container): RoleListHandler
    {
        return new RoleListHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(TemplateRendererInterface::class),
        );
    }
}
