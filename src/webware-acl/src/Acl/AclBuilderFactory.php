<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Acl;

use Phly\EventDispatcher\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\Repository\AclRepositoryInterface;

final class AclBuilderFactory
{
    public function __invoke(ContainerInterface $container): AclBuilder
    {
        $events = $container->has(EventDispatcherInterface::class)
            ? $container->get(EventDispatcherInterface::class)
            : null;

        return new AclBuilder(
            repository: $container->get(AclRepositoryInterface::class),
            cache:      $container->get(AclCacheInterface::class),
            events:     $events,
        );
    }
}
