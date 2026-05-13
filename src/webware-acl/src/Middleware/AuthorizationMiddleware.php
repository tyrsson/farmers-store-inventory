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

namespace Webware\Acl\Middleware;

use Axleus\Log\Event\LogEvent;
use Axleus\Log\LogChannel;
use Axleus\Message\SystemMessengerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Monolog\Level;
use Webware\UserManager\UserInterface;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\AclInterface;

/**
 * Checks whether the current user is allowed to access the matched route.
 *
 * Decision table:
 *  - No RouteResult attribute, or routing failure  → pass through (not our concern)
 *  - Any role grants isAllowed()                   → delegate to next handler
 *  - Unauthenticated (only base role)              → redirect to login (no toast)
 *  - Authenticated but denied                      → toast warning + redirect to home
 *  - Route name has no acl_route_privilege row     → same denial logic as above
 */
final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AclInterface $acl,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly string $loginPath,
        private readonly string $homePath,
        private readonly string $baseRole,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $roles = [...$request->getAttribute(UserInterface::class)->getRoles()];

        if ($this->acl->isAllowedRoute($request, $roles)) {
            return $handler->handle($request);
        }

        // Guest (unauthenticated) — send to login silently
        if ($roles === [$this->baseRole]) {
            return new RedirectResponse($this->loginPath);
        }

        // Authenticated but insufficient privileges — toast + redirect home
        $user      = $request->getAttribute(UserInterface::class);
        $messenger = $request->getAttribute(SystemMessengerInterface::class);
        $messenger?->warning('Insufficient privileges to perform the requested action.', hops: 1, now: false);

        $event = (new LogEvent(LogChannel::Security, Level::Warning))
            ->setMessage('Access denied: {identity} attempted {method} {path}')
            ->setContext([
                'identity' => $user->getIdentity(),
                'method'   => $request->getMethod(),
                'path'     => (string) $request->getUri()->getPath(),
                'roles'    => $roles,
            ]);
        $this->dispatcher->dispatch($event);

        return new RedirectResponse($this->homePath);
    }
}
