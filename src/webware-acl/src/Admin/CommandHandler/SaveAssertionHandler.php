<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Admin\CommandHandler;

use Override;
use Webware\Acl\Admin\Command\SaveAssertionCommand;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

final class SaveAssertionHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof SaveAssertionCommand);

        $id = $this->aclRepository->saveRuleAssertion(
            $command->rulePk,
            $command->assertion,
            $command->mode,
            $command->sortOrder,
        );
        $this->aclRepository->incrementVersion();

        return new CommandResult($command, CommandStatus::Success, $id);
    }
}
