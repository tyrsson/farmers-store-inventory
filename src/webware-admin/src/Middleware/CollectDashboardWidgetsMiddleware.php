<?php

declare(strict_types=1);

namespace Webware\Admin\Middleware;

use Mezzio\Authentication\UserInterface;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\AclInterface;
use Webware\Admin\Event\CollectDashboardWidgetsEvent;
use Webware\Admin\Widget\AclWidgetFilterIterator;

/**
 * Dispatches CollectDashboardWidgetsEvent so that modules may contribute
 * their widgets, then filters the collected widgets through the ACL using
 * the current user's roles, and attaches the filtered iterator to the request.
 *
 * Route this middleware in the admin dashboard route pipeline, after
 * IdentityMiddleware (so the user attribute is already set).
 */
final class CollectDashboardWidgetsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly AclInterface $acl,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var CollectDashboardWidgetsEvent $event */
        $event = $this->dispatcher->dispatch(new CollectDashboardWidgetsEvent());

        $user  = $request->getAttribute(UserInterface::class);
        $roles = $user !== null ? [...$user->getRoles()] : [];

        $widgets = new AclWidgetFilterIterator($event->getIterator(), $this->acl, $roles);

        return $handler->handle(
            $request->withAttribute(CollectDashboardWidgetsEvent::class, $widgets),
        );
    }
}
