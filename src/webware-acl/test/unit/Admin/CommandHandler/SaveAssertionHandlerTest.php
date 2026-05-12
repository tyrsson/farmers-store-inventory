<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveAssertionCommand;
use Webware\Acl\Admin\CommandHandler\SaveAssertionHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(SaveAssertionHandler::class)]
final class SaveAssertionHandlerTest extends TestCase
{
    #[Test]
    public function handleSavesAssertionIncrementsVersionAndReturnsSuccessWithId(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveRuleAssertion')
            ->with(6, 'OwnershipAssertion', 'and', 1)
            ->willReturn(11);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new SaveAssertionCommand(6, 'OwnershipAssertion', 'and', 1);
        $result  = (new SaveAssertionHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertSame(11, $result->getResult());
    }
}
