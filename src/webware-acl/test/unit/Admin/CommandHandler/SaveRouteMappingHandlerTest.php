<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveRouteMappingCommand;
use Webware\Acl\Admin\CommandHandler\SaveRouteMappingHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(SaveRouteMappingHandler::class)]
final class SaveRouteMappingHandlerTest extends TestCase
{
    #[Test]
    public function handleSavesRouteMappingIncrementsVersionAndReturnsSuccess(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveRouteMapping')
            ->with('products.list', 3, 1);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new SaveRouteMappingCommand('products.list', 3, 1);
        $result  = (new SaveRouteMappingHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertNull($result->getResult());
    }
}
