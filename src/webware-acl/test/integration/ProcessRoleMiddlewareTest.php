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
use Webware\Acl\Admin\Middleware\ProcessRoleMiddleware;
use Webware\Acl\Admin\WriteResult;
use Webware\Acl\Repository\AclRepositoryInterface;

#[CoversClass(ProcessRoleMiddleware::class)]
final class ProcessRoleMiddlewareTest extends TestCase
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
    public function postWithValidBodySavesRoleAndSetsSuccessTrue(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('saveRole')
            ->with('Shift Lead', 2);
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '2'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function postWithMissingRoleIdSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['parent_pk' => '2']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function postWithZeroParentPkSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '0']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function patchWithValidBodySavesRole(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('saveRole')
            ->with('Shift Lead', 2);
        $repository->expects($this->once())->method('incrementVersion');

        $request = (new ServerRequest([], [], '/', 'PATCH'))
            ->withParsedBody(['role_id' => 'Shift Lead', 'parent_pk' => '2']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRoleMiddleware($repository);
        $middleware->processPatch($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }
}
