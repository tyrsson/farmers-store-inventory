<?php

declare(strict_types=1);

namespace User\Container;

use Psr\Container\ContainerInterface;
use User\RouteProvider;

final class RouteProviderFactory
{
    public function __invoke(ContainerInterface $container): RouteProvider
    {
        return new RouteProvider();
    }
}
