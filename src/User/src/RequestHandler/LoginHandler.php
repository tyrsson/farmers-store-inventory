<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace User\RequestHandler;

use Axleus\Message\SystemMessengerInterface;
use Htmx\Response\Header as HtmxResponseHeader;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Authentication\UserInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function json_encode;

/**
 * Renders Login page for GET
 * Post Request never reaches this handler as its Handled by
 * the LoginMiddleware. This handler is only for rendering the login page on GET request.
 */
final class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getAttribute(UserInterface::class) !== null) {
            return new RedirectResponse('/');
        }

        $response = new HtmlResponse($this->template->render('user::login'));

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        if ($messenger !== null && $messenger->hasMessages()) {
            foreach ($messenger->getMessages() as $level => $messages) {
                foreach ($messages as $message) {
                    $response = $response->withHeader(
                        HtmxResponseHeader::TriggerAfterSettle->value,
                        (string) json_encode(['systemMessage' => ['level' => $level, 'message' => $message]])
                    );
                }
            }
        }

        return $response;
    }
}
