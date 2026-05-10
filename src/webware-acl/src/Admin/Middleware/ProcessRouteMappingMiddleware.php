<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Admin\Middleware;

use Axleus\Message\SystemMessengerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\DeleteRouteMappingCommand;
use Webware\Acl\Admin\Command\SaveRouteMappingCommand;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessRouteMappingMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistRouteMapping($request, $handler);
    }

    public function processPatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistRouteMapping($request, $handler);
    }

    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $routeName = (string) $request->getAttribute('route_name', '');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new DeleteRouteMappingCommand(''), CommandStatus::Failure, null);

        if ($routeName !== '') {
            $result = $this->commandBus->handle(new DeleteRouteMappingCommand($routeName));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Route mapping deleted.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    private function persistRouteMapping(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $body        = (array) $request->getParsedBody();
        $routeName   = (string) ($body['route_name']   ?? '');
        $resourcePk  = (int)    ($body['resource_pk']  ?? 0);
        $privilegePk = (int)    ($body['privilege_pk'] ?? 0);

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new SaveRouteMappingCommand('', 0, 0), CommandStatus::Failure, null);

        if ($routeName !== '' && $resourcePk > 0 && $privilegePk > 0) {
            $result = $this->commandBus->handle(new SaveRouteMappingCommand($routeName, $resourcePk, $privilegePk));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Route mapping saved.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
