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
use Webware\Acl\Admin\Command\DeleteAssertionCommand;
use Webware\Acl\Admin\Command\SaveAssertionCommand;
use Webware\Acl\Admin\Middleware\ProcessAssertionMiddleware;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;

#[CoversClass(ProcessAssertionMiddleware::class)]
final class ProcessAssertionMiddlewareTest extends TestCase
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

    private function successResult(SaveAssertionCommand|DeleteAssertionCommand $cmd): CommandResult
    {
        return new CommandResult($cmd, CommandStatus::Success, null);
    }

    #[Test]
    public function postWithValidDataDispatchesSaveAssertionCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(CommandBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(SaveAssertionCommand::class))
            ->willReturnCallback(fn($cmd) => $this->successResult($cmd));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withAttribute('rule_id', '3')
            ->withAttribute(SystemMessengerInterface::class, $messenger)
            ->withParsedBody(['assertion' => 'OwnershipAssertion', 'mode' => 'all', 'sort_order' => '1']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
    }

    #[Test]
    public function postWithZeroRuleIdSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withAttribute('rule_id', '0')
            ->withParsedBody(['assertion' => 'OwnershipAssertion', 'mode' => 'all']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function postWithEmptyAssertionSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withAttribute('rule_id', '3')
            ->withParsedBody(['assertion' => '', 'mode' => 'all']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function postWithInvalidModeSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withAttribute('rule_id', '3')
            ->withParsedBody(['assertion' => 'OwnershipAssertion', 'mode' => 'invalid']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }

    #[Test]
    public function deleteWithValidIdDispatchesDeleteAssertionCommandAndSetsSuccess(): void
    {
        $bus = $this->createMock(CommandBusInterface::class);
        $bus->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(DeleteAssertionCommand::class))
            ->willReturnCallback(fn($cmd) => $this->successResult($cmd));

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('id', '8')
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Success, $result->getStatus());
    }

    #[Test]
    public function deleteWithZeroIdSetsFailure(): void
    {
        $bus = $this->createStub(CommandBusInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('id', '0');

        $handler    = $this->capturingHandler();
        $middleware = new ProcessAssertionMiddleware($bus);
        $middleware->process($request, $handler);

        $result = $handler->received?->getAttribute(CommandResult::class);
        self::assertInstanceOf(CommandResult::class, $result);
        self::assertSame(CommandStatus::Failure, $result->getStatus());
    }
}
