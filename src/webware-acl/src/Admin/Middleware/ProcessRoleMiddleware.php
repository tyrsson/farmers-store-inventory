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

final class ProcessRoleMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly AclRepositoryInterface $aclRepository)
    {
    }

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistRole($request, $handler);
    }

    public function processPatch(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->persistRole($request, $handler);
    }

    public function processDelete(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $rolePk = (int) $request->getAttribute('pk');

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $success = false;

        if ($rolePk > 0) {
            $this->aclRepository->deleteRole($rolePk);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Role deleted.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }

    private function persistRole(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $body     = (array) $request->getParsedBody();
        $roleId   = trim((string) ($body['role_id']   ?? ''));
        $parentPk = (int)         ($body['parent_pk'] ?? 0);

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $success = false;

        if ($roleId !== '' && $parentPk > 0) {
            $this->aclRepository->saveRole($roleId, $parentPk);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Role saved.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
