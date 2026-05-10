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
use Webware\Acl\Admin\Middleware\ProcessRuleMiddleware;
use Webware\Acl\Admin\WriteResult;
use Webware\Acl\Repository\AclRepositoryInterface;

#[CoversClass(ProcessRuleMiddleware::class)]
final class ProcessRuleMiddlewareTest extends TestCase
{
    /** Captures the request forwarded to the next handler. */
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
    public function postWithValidBodySavesRuleAndSetsSuccessTrue(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('saveRule')
            ->with(1, 2, 3, 'allow');
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['role_pk' => '1', 'resource_pk' => '2', 'privilege_pk' => '3', 'type' => 'allow'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function postWithMissingRolePkSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_pk' => '2', 'privilege_pk' => '3']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function deleteWithValidIdDeletesRuleAndSetsSuccessTrue(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteRule')->with(42);
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('id', '42')
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($repository);
        $middleware->processDelete($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function deleteWithZeroIdSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['id' => '0']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($repository);
        $middleware->processDelete($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function patchWithValidBodySavesRule(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('updateRuleType')
            ->with(7, 'deny');
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'PATCH'))
            ->withAttribute('id', '7')
            ->withParsedBody(['type' => 'deny'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRuleMiddleware($repository);
        $middleware->processPatch($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }
}
