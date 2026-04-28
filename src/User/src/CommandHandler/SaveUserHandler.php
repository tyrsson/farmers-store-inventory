<?php

declare(strict_types=1);

namespace User\CommandHandler;

use Override;
use User\Repository\UserRepositoryInterface;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

final class SaveUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        throw new \Exception('Not implemented');
    }
}
