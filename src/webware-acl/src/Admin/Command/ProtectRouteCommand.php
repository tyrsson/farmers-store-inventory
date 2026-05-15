<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Command;

use Webware\CommandBus\Command\NamedCommandInterface;
use Webware\CommandBus\Command\NamedCommandTrait;

final readonly class ProtectRouteCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /**
     * @param string[] $allowedMethods  e.g. ['GET', 'POST']
     * @param string[] $roles           role_id values to grant allow rules for
     */
    public function __construct(
        public string $routeName,
        public array  $allowedMethods,
        public array  $roles = [],
    ) {}
}
