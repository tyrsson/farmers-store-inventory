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
use Webware\Acl\Privilege;
use Webware\Acl\Repository\AclRepositoryInterface;
use Webware\Core\HttpMethodProcessorTrait;

final class ProcessResourceMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly AclRepositoryInterface $aclRepository)
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

        $success = false;

        if ($resourcePk > 0) {
            $this->aclRepository->deleteResource($resourcePk);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Resource deleted.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
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

        $success = false;

        if ($resourceId !== '' && $label !== '') {
            $resourcePk = $this->aclRepository->saveResource($resourceId, $label);
            $this->aclRepository->insertPrivilege($resourcePk, Privilege::READ,   'Read');
            $this->aclRepository->insertPrivilege($resourcePk, Privilege::CREATE, 'Create');
            $this->aclRepository->insertPrivilege($resourcePk, Privilege::UPDATE, 'Update');
            $this->aclRepository->insertPrivilege($resourcePk, Privilege::DELETE, 'Delete');
            $this->aclRepository->incrementVersion();
            $messenger?->success('Resource saved.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
