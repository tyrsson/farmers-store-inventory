<?php

declare(strict_types=1);

namespace User\Middleware;

use CuyZ\Valinor\Mapper\TreeMapper;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Command\SaveUserCommand;
use Webware\CommandBus\CommandBusInterface;

final class RegistrationMiddleware implements MiddlewareInterface
{
    public const DEFAULT_ROLE = 'Warehouse';

    public function __construct(
        private readonly TreeMapper $mapper,
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('default_role', self::DEFAULT_ROLE);

        return $handler->handle($request);
    }
}
