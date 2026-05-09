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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\Middleware\AuthorizationMiddleware;

final class AuthorizationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthorizationMiddleware
    {
        $config    = $container->get('config');
        $loginPath = $config['webware-acl']['login_path'] ?? '/login';
        $homePath  = $config['webware-acl']['home_path']  ?? '/';
        $baseRole  = $config['webware-acl']['base_role']  ?? 'guest';

        return new AuthorizationMiddleware(
            acl:        $container->get(AclInterface::class),
            dispatcher: $container->get(EventDispatcherInterface::class),
            loginPath:  $loginPath,
            homePath:   $homePath,
            baseRole:   $baseRole,
        );
    }
}
