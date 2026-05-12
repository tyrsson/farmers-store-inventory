<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteResourceCommand;
use Webware\Acl\Admin\CommandHandler\DeleteResourceHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(DeleteResourceHandler::class)]
final class DeleteResourceHandlerTest extends TestCase
{
    #[Test]
    public function handleDeletesResourceIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteResource')->with(3);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new DeleteResourceCommand(3);
        $result  = (new DeleteResourceHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
