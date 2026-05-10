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
use Webware\Acl\Admin\Middleware\ProcessResourceMiddleware;
use Webware\Acl\Admin\WriteResult;
use Webware\Acl\Privilege;
use Webware\Acl\Repository\AclRepositoryInterface;

#[CoversClass(ProcessResourceMiddleware::class)]
final class ProcessResourceMiddlewareTest extends TestCase
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
    public function postWithValidBodySavesResourceAndSeedsFourPrivileges(): void
    {
        $repository = $this->createMock(AclRepositoryInterface::class);
        $repository->expects($this->once())->method('saveResource')
            ->with('admin.products', 'Products')
            ->willReturn(7);
        $repository->expects($this->exactly(4))->method('insertPrivilege')
            ->willReturnCallback(static function (int $pk, string $privilege): int {
                return 1;
            });
        $repository->expects($this->once())->method('incrementVersion');

        $messenger = $this->createStub(SystemMessengerInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_id' => 'admin.products', 'label' => 'Products'])
            ->withAttribute(SystemMessengerInterface::class, $messenger);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessResourceMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertTrue($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function insertPrivilegeSeedsAllFourCanonicalPrivileges(): void
    {
        $seededPrivileges = [];

        $repository = $this->createStub(AclRepositoryInterface::class);
        $repository->method('saveResource')->willReturn(7);
        $repository->method('insertPrivilege')
            ->willReturnCallback(static function (int $pk, string $privilege) use (&$seededPrivileges): int {
                $seededPrivileges[] = $privilege;
                return 1;
            });
        $repository->method('incrementVersion');

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_id' => 'admin.products', 'label' => 'Products']);

        $middleware = new ProcessResourceMiddleware($repository);
        $middleware->process($request, $this->capturingHandler());

        self::assertSame(
            [Privilege::READ, Privilege::CREATE, Privilege::UPDATE, Privilege::DELETE],
            $seededPrivileges,
        );
    }

    #[Test]
    public function postWithMissingResourceIdSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['label' => 'Products']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessResourceMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }

    #[Test]
    public function postWithMissingLabelSetsSuccessFalse(): void
    {
        $repository = $this->createStub(AclRepositoryInterface::class);

        $request = (new ServerRequest([], [], '/', 'POST'))
            ->withParsedBody(['resource_id' => 'admin.products']);

        $handler    = $this->capturingHandler();
        $middleware = new ProcessResourceMiddleware($repository);
        $middleware->process($request, $handler);

        self::assertFalse($handler->received?->getAttribute(WriteResult::Success->value));
    }
}
