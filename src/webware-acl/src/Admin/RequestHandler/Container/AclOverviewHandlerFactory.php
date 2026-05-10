<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class AclOverviewHandlerFactory
{
    public function __invoke(ContainerInterface $container): AclOverviewHandler
    {
        return new AclOverviewHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(TemplateRendererInterface::class),
        );
    }
}
