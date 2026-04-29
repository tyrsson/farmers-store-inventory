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

namespace User\CommandHandler;

use Override;
use Ramsey\Uuid\Uuid;
use Throwable;
use User\Command\SaveUserCommand;
use User\Middleware\RegistrationMiddleware;
use User\Repository\UserRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

use function password_hash;

final class SaveUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof SaveUserCommand);

        try {
            $roleId = $this->users->findRoleIdByName(RegistrationMiddleware::DEFAULT_ROLE);

            if ($roleId === null) {
                return new CommandResult($command, CommandStatus::Failure, 'Default role not found.');
            }

            $token = Uuid::uuid7()->toString();
            $now   = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

            $id = $this->users->insert([
                'store_id'           => $command->storeId,
                'role_id'            => $roleId,
                'first_name'         => $command->firstName,
                'last_name'          => $command->lastName,
                'email'              => $command->email,
                'password_hash'      => password_hash($command->password, PASSWORD_DEFAULT),
                'active'             => 0,
                'verification_token' => $token,
                'token_created_at'   => $now,
            ]);

            return new CommandResult($command, CommandStatus::Success, $token);
        } catch (Throwable $e) {
            return new CommandResult($command, CommandStatus::Failure, $e->getMessage());
        }
    }
}
