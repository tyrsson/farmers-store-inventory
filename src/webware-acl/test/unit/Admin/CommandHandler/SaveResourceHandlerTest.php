<?php

declare(strict_types=1);

namespace Webware\AclTest\Admin\CommandHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Acl\Admin\Command\SaveResourceCommand;
use Webware\Acl\Admin\CommandHandler\SaveResourceHandler;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;

#[CoversClass(SaveResourceHandler::class)]
final class SaveResourceHandlerTest extends TestCase
{
    #[Test]
    public function handleSavesResourceInsertsFourPrivilegesIncrementsVersionAndReturnsPk(): void
    {
        $repo = $this->createMock(AclRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveResource')
            ->with('products', 'Products')
            ->willReturn(5);
        $repo->expects($this->exactly(4))
            ->method('insertPrivilege')
            ->willReturnOnConsecutiveCalls(1, 2, 3, 4);
        $repo->expects($this->once())->method('incrementVersion');

        $command = new SaveResourceCommand('products', 'Products');
        $result  = (new SaveResourceHandler($repo))->handle($command);

        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
        self::assertSame(5, $result->getResult());
    }
}
