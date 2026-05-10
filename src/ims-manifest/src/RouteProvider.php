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

use Ims\Manifest\Middleware\ProcessManifestUploadMiddleware;
use Ims\Manifest\RequestHandler\ManifestDetailHandler;
use Ims\Manifest\RequestHandler\ManifestListHandler;
use Ims\Manifest\RequestHandler\ManifestUploadHandler;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use Webware\Acl\Middleware\AuthorizationMiddleware;

final class RouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        $routeCollector->get(
            '/manifests',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ManifestListHandler::class,
            ]),
            'manifest.list'
        )->setOptions([
            'navigation' => 'main',
            'label'      => 'Manifests',
            'icon'       => 'bi-clipboard2-data',
            'parent'     => null,
            'order'      => 20,
        ]);

        $routeCollector->get(
            '/manifests/upload',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ManifestUploadHandler::class,
            ]),
            'manifest.upload'
        );

        $routeCollector->post(
            '/manifests/upload',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ProcessManifestUploadMiddleware::class,
                ManifestUploadHandler::class,
            ]),
            'manifest.upload.store'
        );

        $routeCollector->get(
            '/manifests/{id:\d+}',
            $middlewareFactory->prepare([
                AuthorizationMiddleware::class,
                ManifestDetailHandler::class,
            ]),
            'manifest.detail'
        );
    }
}
