<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteAssertionCommand;
use Webware\Acl\Admin\CommandHandler\DeleteAssertionHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(DeleteAssertionHandler::class)]
final class DeleteAssertionHandlerTest extends TestCase
{
    #[Test]
    public function handleDeletesAssertionIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteRuleAssertion')->with(11);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new DeleteAssertionCommand(11);
        $result  = (new DeleteAssertionHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
