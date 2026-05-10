<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ims\Manifest\Middleware\Container;

use Ims\Manifest\Csv\ManifestCsvParser;
use Ims\Manifest\Middleware\ProcessManifestUploadMiddleware;
use Ims\Manifest\Repository\ManifestRepositoryInterface;
use Psr\Container\ContainerInterface;

final class ProcessManifestUploadMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProcessManifestUploadMiddleware
    {
        return new ProcessManifestUploadMiddleware(
            $container->get(ManifestRepositoryInterface::class),
            $container->get(ManifestCsvParser::class),
        );
    }
}
