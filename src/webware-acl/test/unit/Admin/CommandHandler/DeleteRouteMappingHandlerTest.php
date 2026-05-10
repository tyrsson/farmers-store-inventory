<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\DeleteRouteMappingCommand;
use Webware\Acl\Admin\CommandHandler\DeleteRouteMappingHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(DeleteRouteMappingHandler::class)]
final class DeleteRouteMappingHandlerTest extends TestCase
{
    #[Test]
    public function handleDeletesRouteMappingIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())->method('deleteRouteMapping')->with('products.list');
        $repo->expects($this->once())->method('incrementVersion');

        $command = new DeleteRouteMappingCommand('products.list');
        $result  = (new DeleteRouteMappingHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
