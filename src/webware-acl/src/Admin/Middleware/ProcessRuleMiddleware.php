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
use Webware\Acl\Admin\WriteResult;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessRuleMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly AclRepositoryInterface $aclRepository)
    {
    }

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistRule($request, $handler);
    }

    public function processPatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $id   = (int)    $request->getAttribute('id');
        $body = (array)  $request->getParsedBody();
        $type = (string) ($body['type'] ?? 'allow');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $success = false;

        if ($id > 0 && in_array($type, ['allow', 'deny'], true)) {
            $this->aclRepository->updateRuleType($id, $type);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Rule updated.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }

    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $id = (int) $request->getAttribute('id');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $success = false;

        if ($id > 0) {
            $this->aclRepository->deleteRule($id);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Rule deleted.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }

    private function persistRule(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $body        = (array) $request->getParsedBody();
        $rolePk      = (int)    ($body['role_pk']      ?? 0);
        $resourcePk  = (int)    ($body['resource_pk']  ?? 0);
        $privilegePk = (int)    ($body['privilege_pk'] ?? 0);
        $type        = (string) ($body['type']          ?? 'allow');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $success = false;

        if ($rolePk > 0 && $resourcePk > 0 && $privilegePk > 0) {
            $this->aclRepository->saveRule($rolePk, $resourcePk, $privilegePk, $type);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Rule saved.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
