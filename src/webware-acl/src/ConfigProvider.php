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
use Webware\Acl\Middleware\AuthorizationMiddleware;
use Webware\Acl\Middleware\IdentityMiddleware;
use Webware\Acl\Repository\AclRepository;
use Webware\Acl\Repository\AclRepositoryInterface;

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
            'factories' => [
                Acl::class                     => AclFactory::class,
                AclBuilder::class              => AclBuilderFactory::class,
                AclRepository::class           => AclRepositoryFactory::class,
                FileAclCache::class            => FileAclCacheFactory::class,
                AuthorizationMiddleware::class => AuthorizationMiddlewareFactory::class,
                IdentityMiddleware::class      => IdentityMiddlewareFactory::class,
                // Replaces Mezzio\Authentication\DefaultUserFactory so that
                // users with no roles are assigned the configured base role.
                UserInterface::class => DefaultUserFactory::class,
            ],
        ];
    }
}
