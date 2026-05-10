<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteRoleCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRoleHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(DeleteRoleHandler::class)]
final class DeleteRoleHandlerTest extends TestCase
{
    #[Test]
    public function handleDeletesRoleIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteRole')->with(7);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new DeleteRoleCommand(7);
        $result  = (new DeleteRoleHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
