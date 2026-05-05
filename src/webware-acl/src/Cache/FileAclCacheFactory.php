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

namespace Webware\Acl\Cache;

use Psr\Container\ContainerInterface;

final class FileAclCacheFactory
{
    public function __invoke(ContainerInterface $container): FileAclCache
    {
        $config   = $container->get('config');
        $cacheDir = $config['webware-acl']['cache_dir'] ?? 'data/cache';

        return new FileAclCache(cacheDir: $cacheDir);
    }
}
