<?php

declare(strict_types=1);


namespace Webware\Admin\Container;

use Psr\Container\ContainerInterface;
use Webware\Admin\RouteProvider;

final class RouteProviderFactory
{
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        return new RouteProvider();
    }
}
