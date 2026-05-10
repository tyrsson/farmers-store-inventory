<?php

declare(strict_types=1);


namespace Webware\Acl\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Listener\RegisterAclWidgetListener;
use Webware\Acl\Repository\AclRepositoryInterface;

final class RegisterAclWidgetListenerFactory
{
    public function __invoke(ContainerInterface $container): RegisterAclWidgetListener
    {
        return new RegisterAclWidgetListener(
            $container->get(AclRepositoryInterface::class),
        );
    }
}
