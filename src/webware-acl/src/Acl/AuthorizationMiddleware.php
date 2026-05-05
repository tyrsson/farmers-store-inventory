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

namespace Webware\Acl\Acl;

use Axleus\Message\SystemMessengerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks whether the current user is allowed to access the matched route.
 *
 * Decision table:
 *  - No RouteResult attribute, or routing failure  → pass through (not our concern)
 *  - Any role grants isAllowed()                   → delegate to next handler
 *  - No role grants access, role === baseRole      → redirect to login
 *  - No role grants access, authenticated user     → toast warning + redirect to home
 *  - Route name has no acl_route_privilege row     → same denial logic as above
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AclInterface $acl,
        private readonly string $loginPath,
    ) {
    }

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $roles = [...$request->getAttribute(UserInterface::class)->getRoles()];

        if ($this->acl->isAllowedRoute($request, $roles)) {
            return $handler->handle($request);
        }

        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->warning('You do not have permission to perform that action.', hops: 1, now: false);

        return new RedirectResponse($this->loginPath);
    }
}
