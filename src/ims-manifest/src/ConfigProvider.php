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

use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\Event\ResourcesLoadedEvent;
use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider as BusProvider;

final readonly class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'           => $this->getDependencies(),
            'listeners'              => $this->getListeners(),
            'router'                 => $this->getRouteProviders(),
            'templates'              => $this->getTemplates(),
            CommandBusInterface::class => $this->getBusConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                Repository\ManifestRepositoryInterface::class => Repository\ManifestRepository::class,
            ],
            'factories' => [
                Repository\ManifestRepository::class                                              => Repository\ManifestRepositoryFactory::class,
                RequestHandler\ManifestListHandler::class                                         => RequestHandler\Container\ManifestListHandlerFactory::class,
                RequestHandler\ManifestDetailHandler::class                                       => RequestHandler\Container\ManifestDetailHandlerFactory::class,
                RequestHandler\ManifestUploadHandler::class                                       => RequestHandler\Container\ManifestUploadHandlerFactory::class,
                Middleware\ProcessManifestUploadMiddleware::class                                  => Middleware\Container\ProcessManifestUploadMiddlewareFactory::class,
                Csv\ManifestCsvParser::class                                                      => Csv\ManifestCsvParserFactory::class,
                RouteProvider::class                                                               => Container\RouteProviderFactory::class,
                Listener\RegisterManifestResourcesListener::class                                 => Container\RegisterManifestResourcesListenerFactory::class,
                Listener\RegisterManifestRulesListener::class                                     => Container\RegisterManifestRulesListenerFactory::class,
                Listener\RegisterManifestWidgetListener::class                                    => Container\RegisterManifestWidgetListenerFactory::class,
                CommandHandler\UploadManifestHandler::class                                       => CommandHandler\Container\UploadManifestHandlerFactory::class,
            ],
        ];
    }

    public function getListeners(): array
    {
        return [
            RegisterWidgetEvent::class  => [
                ['listener' => Listener\RegisterManifestWidgetListener::class, 'priority' => 1],
            ],
            ResourcesLoadedEvent::class => [
                ['listener' => Listener\RegisterManifestResourcesListener::class, 'priority' => 1],
            ],
            RulesLoadedEvent::class     => [
                ['listener' => Listener\RegisterManifestRulesListener::class, 'priority' => 1],
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

    public function getBusConfig(): array
    {
        return [
            BusProvider::COMMAND_MAP_KEY => [
                Command\UploadManifestCommand::class => CommandHandler\UploadManifestHandler::class,
            ],
        ];
    }
}
