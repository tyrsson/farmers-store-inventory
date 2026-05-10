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
use Webware\Acl\Admin\Command\SaveResourceCommand;
use Webware\Acl\Privilege;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

final class SaveResourceHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly AclRepositoryInterface $aclRepository,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof SaveResourceCommand);

        $resourcePk = $this->aclRepository->saveResource($command->resourceId, $command->label);
        $this->aclRepository->insertPrivilege($resourcePk, Privilege::READ,   'Read');
        $this->aclRepository->insertPrivilege($resourcePk, Privilege::CREATE, 'Create');
        $this->aclRepository->insertPrivilege($resourcePk, Privilege::UPDATE, 'Update');
        $this->aclRepository->insertPrivilege($resourcePk, Privilege::DELETE, 'Delete');
        $this->aclRepository->incrementVersion();

        return new CommandResult($command, CommandStatus::Success, $resourcePk);
    }
}
