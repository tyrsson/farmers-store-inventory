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

use Psr\Container\ContainerInterface;

final class AuthorizationMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): AuthorizationMiddleware
    {
        $config    = $container->get('config');
        $loginPath = $config['webware-acl']['login_path'] ?? '/login';

        return new AuthorizationMiddleware(
            acl:       $container->get(AclInterface::class),
            loginPath: $loginPath,
        );
    }
}
