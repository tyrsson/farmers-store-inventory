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
use Webware\Acl\Admin\Command\DeleteAssertionCommand;
use Webware\Acl\Admin\Command\SaveAssertionCommand;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;
use Webware\Core\HttpMethodProcessorTrait;

use function in_array;

final class ProcessAssertionMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $ruleId    = (int)    $request->getAttribute('rule_id');
        $body      = (array)  $request->getParsedBody();
        $assertion = (string) ($body['assertion']  ?? '');
        $mode      = (string) ($body['mode']       ?? 'all');
        $sortOrder = (int)    ($body['sort_order'] ?? 0);

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new SaveAssertionCommand(0, '', 'all', 0), CommandStatus::Failure, null);

        if ($ruleId > 0 && $assertion !== '' && in_array($mode, ['all', 'at_least_one'], true)) {
            $result = $this->commandBus->handle(new SaveAssertionCommand($ruleId, $assertion, $mode, $sortOrder));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Assertion added.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }

    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $id = (int) $request->getAttribute('id');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $result = new CommandResult(new DeleteAssertionCommand(0), CommandStatus::Failure, null);

        if ($id > 0) {
            $result = $this->commandBus->handle(new DeleteAssertionCommand($id));
            if ($result->getStatus() === CommandStatus::Success) {
                $messenger?->success('Assertion removed.');
            }
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
