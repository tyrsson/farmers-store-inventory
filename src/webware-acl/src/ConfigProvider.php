<?php

declare(strict_types=1);

namespace Webware\Acl;

use Mezzio\Authentication\UserInterface;
use Webware\Acl\Acl;
use Webware\Acl\AclBuilder;
use Webware\Acl\AclInterface;
use Webware\Acl\Authentication\DefaultUserFactory;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\Cache\FileAclCache;
use Webware\Acl\Container\AclBuilderFactory;
use Webware\Acl\Container\AclFactory;
use Webware\Acl\Container\AclRepositoryFactory;
use Webware\Acl\Container\AuthorizationMiddlewareFactory;
use Webware\Acl\Container\FileAclCacheFactory;
use Webware\Acl\Container\IdentityMiddlewareFactory;
use Webware\Acl\Container\RegisterAclWidgetListenerFactory;
use Webware\Acl\Container\RouteProviderFactory;
use Webware\Acl\Admin\RequestHandler\AclOverviewHandler;
use Webware\Acl\Admin\RequestHandler\Container\AclOverviewHandlerFactory;
use Webware\Acl\Admin\RequestHandler\Container\ResourceListHandlerFactory;
use Webware\Acl\Admin\RequestHandler\Container\RoleListHandlerFactory;
use Webware\Acl\Admin\RequestHandler\Container\RouteMapManagerHandlerFactory;
use Webware\Acl\Admin\RequestHandler\Container\RuleManagerHandlerFactory;
use Webware\Acl\Admin\RequestHandler\ResourceListHandler;
use Webware\Acl\Admin\RequestHandler\RoleListHandler;
use Webware\Acl\Admin\RequestHandler\RouteMapManagerHandler;
use Webware\Acl\Admin\RequestHandler\RuleManagerHandler;
use Webware\Acl\Event\AclBuiltEvent;
use Webware\Acl\Event\ResourcesLoadedEvent;
use Webware\Acl\Event\RulesLoadedEvent;
use Webware\Acl\Listener\RegisterAclResourcesListener;
use Webware\Acl\Listener\RegisterAclRouteMappingsListener;
use Webware\Acl\Listener\RegisterAclRulesListener;
use Webware\Acl\Listener\RegisterAclWidgetListener;
use Webware\Acl\Listener\RegisterOwnershipAssertionListener;
use Webware\Acl\Middleware\AuthorizationMiddleware;
use Webware\Acl\Middleware\IdentityMiddleware;
use Webware\Acl\Admin\Middleware\Container\ProcessAssertionMiddlewareFactory;
use Webware\Acl\Admin\Middleware\Container\ProcessResourceMiddlewareFactory;
use Webware\Acl\Admin\Middleware\Container\ProcessRoleMiddlewareFactory;
use Webware\Acl\Admin\Middleware\Container\ProcessRouteMappingMiddlewareFactory;
use Webware\Acl\Admin\Middleware\Container\ProcessRuleMiddlewareFactory;
use Webware\Acl\Admin\Middleware\ProcessAssertionMiddleware;
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRouteMappingMiddleware;
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\Repository\AclRepository;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\Admin\Event\RegisterWidgetEvent;

final class ConfigProvider
{
    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'listeners'    => $this->getListeners(),
            'router'       => $this->getRouteProviders(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                AclRepositoryInterface::class => AclRepository::class,
                AclCacheInterface::class      => FileAclCache::class,
                AclInterface::class           => Acl::class,
            ],
            'invokables' => [
                RegisterAclResourcesListener::class       => RegisterAclResourcesListener::class,
                RegisterAclRouteMappingsListener::class   => RegisterAclRouteMappingsListener::class,
                RegisterAclRulesListener::class           => RegisterAclRulesListener::class,
                RegisterOwnershipAssertionListener::class => RegisterOwnershipAssertionListener::class,
            ],
            'factories' => [
                Acl::class                        => AclFactory::class,
                AclBuilder::class                 => AclBuilderFactory::class,
                AclOverviewHandler::class         => AclOverviewHandlerFactory::class,
                AclRepository::class              => AclRepositoryFactory::class,
                FileAclCache::class               => FileAclCacheFactory::class,
                AuthorizationMiddleware::class    => AuthorizationMiddlewareFactory::class,
                IdentityMiddleware::class         => IdentityMiddlewareFactory::class,
                RegisterAclWidgetListener::class  => RegisterAclWidgetListenerFactory::class,
                ResourceListHandler::class        => ResourceListHandlerFactory::class,
                RoleListHandler::class            => RoleListHandlerFactory::class,
                RouteMapManagerHandler::class     => RouteMapManagerHandlerFactory::class,
                RouteProvider::class              => RouteProviderFactory::class,
                RuleManagerHandler::class         => RuleManagerHandlerFactory::class,
                ProcessRuleMiddleware::class         => ProcessRuleMiddlewareFactory::class,
                ProcessRoleMiddleware::class         => ProcessRoleMiddlewareFactory::class,
                ProcessRouteMappingMiddleware::class => ProcessRouteMappingMiddlewareFactory::class,
                ProcessResourceMiddleware::class     => ProcessResourceMiddlewareFactory::class,
                ProcessAssertionMiddleware::class    => ProcessAssertionMiddlewareFactory::class,
                // Replaces Mezzio\Authentication\DefaultUserFactory so that
                // users with no roles are assigned the configured base role.
                UserInterface::class => DefaultUserFactory::class,
            ],
        ];
    }

    public function getTemplates(): array
    {
        return [
            'paths' => [
                'acl' => [__DIR__ . '/../templates/acl'],
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

    public function getListeners(): array
    {
        return [
            RegisterWidgetEvent::class  => [
                ['listener' => RegisterAclWidgetListener::class, 'priority' => 1],
            ],
            ResourcesLoadedEvent::class => [
                ['listener' => RegisterAclResourcesListener::class, 'priority' => 1],
            ],
            RulesLoadedEvent::class     => [
                ['listener' => RegisterAclRulesListener::class, 'priority' => 1],
            ],
            AclBuiltEvent::class        => [
                ['listener' => RegisterOwnershipAssertionListener::class, 'priority' => 1],
                ['listener' => RegisterAclRouteMappingsListener::class,   'priority' => 2],
            ],
        ];
    }
}
