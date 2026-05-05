<?php

declare(strict_types=1);

namespace Webware\Acl;

use Mezzio\Authentication\UserInterface;
use Webware\Acl\Acl\AclBuilder;
use Webware\Acl\Acl\AclBuilderFactory;
use Webware\Acl\Acl\AuthorizationMiddleware;
use Webware\Acl\Acl\AuthorizationMiddlewareFactory;
use Webware\Acl\Authentication\DefaultUserFactory;
use Webware\Acl\Authentication\IdentityMiddleware;
use Webware\Acl\Authentication\IdentityMiddlewareFactory;
use Webware\Acl\Cache\AclCacheInterface;
use Webware\Acl\Cache\FileAclCache;
use Webware\Acl\Cache\FileAclCacheFactory;
use Webware\Acl\Repository\AclRepository;
use Webware\Acl\Repository\AclRepositoryFactory;
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
                Acl\AclInterface::class       => Acl\Acl::class,
            ],
            'factories' => [
                Acl\Acl::class                 => Acl\AclFactory::class,
                AclRepository::class           => AclRepositoryFactory::class,
                FileAclCache::class            => FileAclCacheFactory::class,
                AclBuilder::class              => AclBuilderFactory::class,
                AuthorizationMiddleware::class => AuthorizationMiddlewareFactory::class,
                IdentityMiddleware::class      => IdentityMiddlewareFactory::class,
                // Replaces Mezzio\Authentication\DefaultUserFactory so that
                // users with no roles are assigned the configured base role.
                UserInterface::class => DefaultUserFactory::class,
            ],
        ];
    }
}
