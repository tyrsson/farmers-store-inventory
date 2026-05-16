<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware;

use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\AclInterface;
use Webware\Acl\RequestHandler\ForbiddenHandlerInterface;
use Webware\UserManager\UserInterface;

final class AuthorizingDispatchMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AclInterface $acl,
        private readonly ForbiddenHandlerInterface $forbiddenHandler,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);

        if (! $routeResult instanceof RouteResult) {
            // No route matched — pass through (MethodNotAllowedMiddleware handles this)
            return $handler->handle($request);
        }

        $user  = $request->getAttribute(UserInterface::class);
        $roles = $user?->getRoles() ?? [];

        if (! $this->acl->isAllowedRoute($request, $roles)) {
            return $this->forbiddenHandler->handle($request);
        }

        return $routeResult->process($request, $handler);
    }
}
