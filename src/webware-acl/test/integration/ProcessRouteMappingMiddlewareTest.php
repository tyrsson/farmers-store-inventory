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
use Webware\Acl\Admin\Middleware\ProcessRouteMappingMiddleware;
use Webware\Acl\Admin\WriteResult;
use Webware\Acl\Repository\AclRepositoryInterface;

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
    public function postWithValidBodySavesMappingAndSetsSuccessTrue(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('saveRouteMapping')
            ->with('admin.acl.rules.read', 2, 3);
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['route_name' => 'admin.acl.rules.read', 'resource_pk' => '2', 'privilege_pk' => '3'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function postWithMissingRouteNameSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_pk' => '2', 'privilege_pk' => '3']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function deleteWithValidRouteNameDeletesMappingAndSetsSuccessTrue(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('deleteRouteMapping')
            ->with('admin.acl.rules.read');
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'DELETE'))
            ->withAttribute('route_name', 'admin.acl.rules.read')
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($repository);
        $middleware->processDelete($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function deleteWithEmptyRouteNameSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['route_name' => '']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessRouteMappingMiddleware($repository);
        $middleware->processDelete($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }
}
