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

namespace Webware\Acl\Repository;

use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

final class AclRepositoryFactory
{
    public function __invoke(ContainerInterface $container): AclRepository
    {
        return new AclRepository(
            adapter: $container->get(AdapterInterface::class),
        );
    }
}
