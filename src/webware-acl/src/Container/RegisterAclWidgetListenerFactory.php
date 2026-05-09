<?php

declare(strict_types=1);


namespace Webware\Acl\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\Listener\RegisterAclWidgetListener;

final class RegisterAclWidgetListenerFactory
{
    public function __invoke(ContainerInterface $container): RegisterAclWidgetListener
    {
        return new RegisterAclWidgetListener();
    }
}
