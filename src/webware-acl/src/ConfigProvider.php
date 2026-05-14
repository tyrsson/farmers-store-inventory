<?php

declare(strict_types=1);

namespace Webware\Acl;

use Mezzio\Authentication\UserInterface;
use Webware\Acl\Acl;
use Webware\Acl\AclBuilder;
use Webware\Acl\AclInterface;
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
use Webware\Acl\Admin\Command\DeleteAssertionCommand;
use Webware\Acl\Admin\Command\DeleteResourceCommand;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\Command\DeleteRouteMappingCommand;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\Command\SaveAssertionCommand;
use Webware\Acl\Admin\Command\SaveResourceCommand;
use Webware\Acl\Admin\Command\SaveRoleCommand;
use Webware\Acl\Admin\Command\SaveRouteMappingCommand;
use Webware\Acl\Admin\Command\SaveRuleCommand;
use Webware\Acl\Admin\Command\UpdateRuleTypeCommand;
use Webware\Acl\Admin\CommandHandler\Container\DeleteAssertionHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\DeleteResourceHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRouteMappingHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\DeleteRuleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveAssertionHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveResourceHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveRoleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveRouteMappingHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\SaveRuleHandlerFactory;
use Webware\Acl\Admin\CommandHandler\Container\UpdateRuleTypeHandlerFactory;
use Webware\Acl\Admin\CommandHandler\DeleteAssertionHandler;
use Webware\Acl\Admin\CommandHandler\DeleteResourceHandler;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Admin\CommandHandler\DeleteRouteMappingHandler;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Admin\CommandHandler\SaveAssertionHandler;
use Webware\Acl\Admin\CommandHandler\SaveResourceHandler;
use Webware\Acl\Admin\CommandHandler\SaveRoleHandler;
use Webware\Acl\Admin\CommandHandler\SaveRouteMappingHandler;
use Webware\Acl\Admin\CommandHandler\SaveRuleHandler;
use Webware\Acl\Admin\CommandHandler\UpdateRuleTypeHandler;
use Webware\Admin\Event\RegisterWidgetEvent;
use Webware\Acl\Container\CommandHandlerMiddlewareFactory;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\ConfigProvider as BusProvider;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;

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
                DeleteAssertionHandler::class      => DeleteAssertionHandlerFactory::class,
                DeleteResourceHandler::class       => DeleteResourceHandlerFactory::class,
                DeleteRoleHandler::class           => DeleteRoleHandlerFactory::class,
                DeleteRouteMappingHandler::class   => DeleteRouteMappingHandlerFactory::class,
                DeleteRuleHandler::class           => DeleteRuleHandlerFactory::class,
                SaveAssertionHandler::class        => SaveAssertionHandlerFactory::class,
                SaveResourceHandler::class         => SaveResourceHandlerFactory::class,
                SaveRoleHandler::class             => SaveRoleHandlerFactory::class,
                SaveRouteMappingHandler::class     => SaveRouteMappingHandlerFactory::class,
                SaveRuleHandler::class             => SaveRuleHandlerFactory::class,
                UpdateRuleTypeHandler::class       => UpdateRuleTypeHandlerFactory::class,
                CommandHandlerMiddleware::class     => CommandHandlerMiddlewareFactory::class,
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

    public function getBusConfig(): array
    {
        return [
            BusProvider::COMMAND_MAP_KEY => [
                SaveRoleCommand::class           => SaveRoleHandler::class,
                DeleteRoleCommand::class         => DeleteRoleHandler::class,
                SaveResourceCommand::class       => SaveResourceHandler::class,
                DeleteResourceCommand::class     => DeleteResourceHandler::class,
                SaveRuleCommand::class           => SaveRuleHandler::class,
                UpdateRuleTypeCommand::class     => UpdateRuleTypeHandler::class,
                DeleteRuleCommand::class         => DeleteRuleHandler::class,
                SaveRouteMappingCommand::class   => SaveRouteMappingHandler::class,
                DeleteRouteMappingCommand::class => DeleteRouteMappingHandler::class,
                SaveAssertionCommand::class      => SaveAssertionHandler::class,
                DeleteAssertionCommand::class    => DeleteAssertionHandler::class,
            ],
        ];
    }
}
