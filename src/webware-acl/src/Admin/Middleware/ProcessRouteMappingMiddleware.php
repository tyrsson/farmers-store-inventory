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

final class ProcessRouteMappingMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly AclRepositoryInterface $aclRepository)
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

        $success = false;

        if ($routeName !== '') {
            $this->aclRepository->deleteRouteMapping($routeName);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Route mapping deleted.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
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

        $success = false;

        if ($routeName !== '' && $resourcePk > 0 && $privilegePk > 0) {
            $this->aclRepository->saveRouteMapping($routeName, $resourcePk, $privilegePk);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Route mapping saved.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
