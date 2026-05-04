<?php

declare(strict_types=1);

namespace App\View\Helper;

use Psr\Container\ContainerInterface;

final class ImsMessengerFactory
{
    public function __invoke(ContainerInterface $container): ImsMessenger
    {
        return new ImsMessenger();
    }
}
