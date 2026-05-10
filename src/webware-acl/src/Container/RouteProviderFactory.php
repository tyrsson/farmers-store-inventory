<?php

declare(strict_types=1);


namespace Webware\Acl\Container;

use Psr\Container\ContainerInterface;
use Webware\Acl\RouteProvider;

final class RouteProviderFactory
{
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        return new RouteProvider();
    }
}
