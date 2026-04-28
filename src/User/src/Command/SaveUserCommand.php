<?php

declare(strict_types=1);

namespace User\Command;

use Mezzio\Authentication\UserInterface;
use Webware\CommandBus\Command\NamedCommandInterface;
use Webware\CommandBus\Command\NamedCommandTrait;

final readonly class SaveUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public UserInterface $user,
    ) {}
}
