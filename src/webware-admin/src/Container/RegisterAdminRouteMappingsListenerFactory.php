<?php

declare(strict_types=1);


namespace Webware\Admin\Container;

use Psr\Container\ContainerInterface;
use Webware\Admin\Listener\RegisterAdminRouteMappingsListener;

final class RegisterAdminRouteMappingsListenerFactory
{
    public function __invoke(ContainerInterface $container): RegisterAdminRouteMappingsListener
    {
        return new RegisterAdminRouteMappingsListener();
    }
}
