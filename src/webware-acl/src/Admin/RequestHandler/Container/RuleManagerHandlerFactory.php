<?php

declare(strict_types=1);


namespace Webware\Acl\Admin\RequestHandler\Container;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\Admin\RequestHandler\RuleManagerHandler;
use Webware\Acl\Repository\AclRepositoryInterface;

final class RuleManagerHandlerFactory
{
    public function __invoke(ContainerInterface $container): RuleManagerHandler
    {
        return new RuleManagerHandler(
            $container->get(AclRepositoryInterface::class),
            $container->get(TemplateRendererInterface::class),
        );
    }
}
