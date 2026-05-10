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

namespace Ims\Manifest;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'router'       => $this->getRouteProviders(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                Repository\ManifestRepositoryInterface::class => Repository\ManifestRepository::class,
            ],
            'factories' => [
                Repository\ManifestRepository::class               => Repository\ManifestRepositoryFactory::class,
                RequestHandler\ManifestListHandler::class          => RequestHandler\Container\ManifestListHandlerFactory::class,
                RequestHandler\ManifestDetailHandler::class        => RequestHandler\Container\ManifestDetailHandlerFactory::class,
                RouteProvider::class                               => Container\RouteProviderFactory::class,
            ],
        ];
    }

    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'manifest' => [__DIR__ . '/../templates/manifest'],
            ],
        ];
    }
}
