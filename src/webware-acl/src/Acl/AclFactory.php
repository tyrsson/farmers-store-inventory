<?php

declare(strict_types=1);

namespace Webware\Acl\Acl;

use Psr\Container\ContainerInterface;

final readonly class AclFactory
{
    public function __invoke(ContainerInterface $container): Acl
    {
        $aclBuilder = $container->get(AclBuilder::class);
        $laminas    = $aclBuilder->build();

        return new Acl(
            acl:           $laminas,
            routeMappings: $aclBuilder->getRouteMappings(),
        );
    }
}
