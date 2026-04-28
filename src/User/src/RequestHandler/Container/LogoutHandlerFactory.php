<?php

declare(strict_types=1);

namespace User\RequestHandler\Container;

use Psr\Container\ContainerInterface;
use User\RequestHandler\LogoutHandler;

final class LogoutHandlerFactory
{
    public function __invoke(ContainerInterface $container): LogoutHandler
    {
        return new LogoutHandler();
    }
}
