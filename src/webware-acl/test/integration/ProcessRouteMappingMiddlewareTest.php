<?php

declare(strict_types=1);

namespace Webware\AclIntegrationTest;

use Axleus\Message\SystemMessengerInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRouteMappingCommand;
use Webware\Acl\Admin\Command\SaveRouteMappingCommand;
use Webware\Acl\Admin\Middleware\ProcessRouteMappingMiddleware;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;

#[CoversClass(ProcessRouteMappingMiddleware::class)]
final class ProcessRouteMappingMiddlewareTest extends TestCase
{
    private function capturingHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public ?ServerRequestInterface $received = null;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->received = $request;
                return new EmptyResponse();
            }
        };
    }

    #[Test]
    public function postWithValidBodyDispatchesSaveMappingCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(CommandBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveRouteMappingCommand::class))
            ->willReturnCallback(fn($cmd) => new CommandResult($cmd, CommandStatus::Success, null));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['route_name' => 'admin.acl.rules.read', 'resource_pk' => '2', 'privilege_pk' => '3'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
    }

    #[Test]
    public function postWithMissingRouteNameSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_pk' => '2', 'privilege_pk' => '3']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function deleteWithValidRouteNameDispatchesDeleteCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(CommandBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(DeleteRouteMappingCommand::class))
            ->willReturnCallback(fn($cmd) => new CommandResult($cmd, CommandStatus::Success, null));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('route_name', 'admin.acl.rules.read')
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($bus);
        $middleware->processDelete($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
    }

    #[Test]
    public function deleteWithEmptyRouteNameSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('route_name', '');

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($bus);
        $middleware->processDelete($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }
}
