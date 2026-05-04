<?php

declare(strict_types=1);

namespace App\Middleware;

use App\View\Helper\ImsMessenger;
use Axleus\Message\SystemMessenger;
use Axleus\Message\SystemMessengerInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ImsMessengerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ImsMessenger $helper,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $messenger = new SystemMessenger(
            $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
            SystemMessengerInterface::SESSION_KEY
        );

        $this->helper->setMessenger($messenger);

        return $handler->handle(
            $request->withAttribute(SystemMessengerInterface::class, $messenger)
        );
    }
}
