<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteRuleCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRuleHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(DeleteRuleHandler::class)]
final class DeleteRuleHandlerTest extends TestCase
{
    #[Test]
    public function handleDeletesRuleIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteRule')->with(4);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new DeleteRuleCommand(4);
        $result  = (new DeleteRuleHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
