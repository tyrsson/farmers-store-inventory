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
use Webware\Acl\Admin\Command\DeleteResourceCommand;
use Webware\Acl\Admin\Command\SaveResourceCommand;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessResourceMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistResource($request, $handler);
    }

    public function processPatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistResource($request, $handler);
    }

    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $resourcePk = (int) $request->getAttribute('pk');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new DeleteResourceCommand(0), CommandStatus::Failure, null);

        if ($resourcePk > 0) {
            $result = $this->commandBus->handle(new DeleteResourceCommand($resourcePk));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Resource deleted.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    private function persistResource(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $body       = (array) $request->getParsedBody();
        $resourceId = trim((string) ($body['resource_id'] ?? ''));
        $label      = trim((string) ($body['label']       ?? ''));

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new SaveResourceCommand('', ''), CommandStatus::Failure, null);

        if ($resourceId !== '' && $label !== '') {
            $result = $this->commandBus->handle(new SaveResourceCommand($resourceId, $label));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Resource saved.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
