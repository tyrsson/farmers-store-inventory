<?php

declare(strict_types=1);

namespace Webware\Acl\Middleware;

use Mezzio\Router\RouterInterface;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Http\RouteResource;

final class RouteMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly array $paramMap = [],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result  = $this->router->match($request);
        $request = $request->withAttribute(RouteResult::class, $result);

        if ($result->isSuccess()) {
            foreach ($result->getMatchedParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }

            // Attach enriched RouteResource for AuthorizingDispatchMiddleware and downstream
            $request = $request->withAttribute(
                RouteResource::class,
                new RouteResource($result, $request, $this->paramMap)
            );
        }

        return $handler->handle($request);
    }
}
