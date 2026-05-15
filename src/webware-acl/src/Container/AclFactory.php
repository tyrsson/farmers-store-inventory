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
use Webware\Acl\Acl;
use Webware\Acl\AclInterface;

final readonly class AclFactory
{
    public function __invoke(ContainerInterface $container): Acl
    {
        $aclBuilder = $container->get(\Webware\Acl\AclBuilder::class);
        $laminas    = $aclBuilder->build();
        $paramMap   = $container->get('config')[AclInterface::class]['route_param_map'] ?? [];

        return new Acl(
            acl:      $laminas,
            paramMap: $paramMap,
        );
    }
}
