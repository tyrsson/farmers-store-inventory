<?php

declare(strict_types=1);

namespace Webware\Acl\Admin\Middleware;

use Axleus\Message\SystemMessengerInterface;
use Mezzio\Router\RouteCollectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\Command\ProtectRouteCommand;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBusInterface;
use Webware\Core\HttpMethodProcessorTrait;

use function array_filter;
use function array_map;
use function array_values;
use function is_array;
use function strval;

final class ProcessProtectRouteMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RouteCollectorInterface $routeCollector,
    ) {}

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $body      = (array) $request->getParsedBody();
        $routeName = (string) ($body['routeName'] ?? '');
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        // Resolve allowed methods from the registered route definition
        $allowedMethods = ['GET'];
        foreach ($this->routeCollector->getRoutes() as $route) {
            if ($route->getName() === $routeName) {
                $allowedMethods = $route->getAllowedMethods() ?? ['GET'];
                break;
            }
        }

        $roles = array_values(array_filter(array_map(
            strval(...),
            is_array($body['role'] ?? null) ? $body['role'] : [],
        )));

        $result = $this->commandBus->handle(
            new ProtectRouteCommand($routeName, $allowedMethods, $roles)
        );

        if ($result->getStatus() === CommandStatus::Success) {
            $messenger?->success("Route '{$routeName}' is now protected.", hops: 0, now: true);
        } else {
            $messenger?->error("Failed to protect route '{$routeName}'.", hops: 0, now: true);
        }

        return $handler->handle($request->withAttribute(CommandResult::class, $result));
    }
}
