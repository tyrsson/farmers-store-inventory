<?php

declare(strict_types=1);

namespace App\Middleware;

use App\View\Helper\ImsMessenger;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

final class ImsMessengerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ImsMessengerMiddleware
    {
        /** @var HelperPluginManager $helpers */
        $helpers = $container->get(HelperPluginManager::class);

        return new ImsMessengerMiddleware(
            $helpers->get(ImsMessenger::class),
        );
    }
}
