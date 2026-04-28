<?php

declare(strict_types=1);

namespace User\Repository;

use Mezzio\Authentication\UserInterface;
use PhpDb\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

final class UserRepositoryFactory
{
    /**
     * @todo Once phpdb readonly-clone support is merged, replace the manual
     *       hydrate() call in UserRepository with a HydratingResultSet using
     *       a constructor-aware hydrator and User::class as the row prototype.
     *       Pass the HydratingResultSet as the $resultSetPrototype argument to
     *       the TableGateway constructor here.
     */
    public function __invoke(ContainerInterface $container): UserRepository
    {
        return new UserRepository(
            adapter:     $container->get(AdapterInterface::class),
            userFactory: $container->get(UserInterface::class),
        );
    }
}

