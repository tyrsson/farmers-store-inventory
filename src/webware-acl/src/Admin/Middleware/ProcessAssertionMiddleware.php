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

use function in_array;

final class ProcessAssertionMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(private readonly AclRepositoryInterface $aclRepository)
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

        $success = false;

        if ($ruleId > 0 && $assertion !== '' && in_array($mode, ['all', 'at_least_one'], true)) {
            $this->aclRepository->saveRuleAssertion($ruleId, $assertion, $mode, $sortOrder);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Assertion added.');
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
            $this->aclRepository->deleteRuleAssertion($id);
            $this->aclRepository->incrementVersion();
            $messenger?->success('Assertion removed.');
            $success = true;
        }

        return $handler->handle($request->withAttribute(WriteResult::Success->value, $success));
    }
}
