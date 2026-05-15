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

namespace Webware\Acl\Container;

use Mezzio\Authentication\AuthenticationInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Container\ContainerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Middleware\IdentityMiddleware;

final class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): IdentityMiddleware
    {
        $config   = $container->get('config');
        $baseRole = (string) ($config[AclInterface::class]['base_role'] ?? 'guest');

        return new IdentityMiddleware(
            userFactory: $container->get(UserInterface::class),
            baseRole:    $baseRole,
            auth:        $container->get(AuthenticationInterface::class),
        );
    }
}
