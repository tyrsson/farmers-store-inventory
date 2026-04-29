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

use Htmx\Request\Header as HtmxRequestHeader;
use Htmx\Response\Header as HtmxResponseHeader;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\CommandBus\Command\CommandStatus;

final class RegistrationHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result = $request->getAttribute('registration_result');

        if ($result !== null && $result->getStatus() === CommandStatus::Success) {
            if ($request->hasHeader(HtmxRequestHeader::Request->value)) {
                return new HtmlResponse('', 200, [HtmxResponseHeader::Location->value => '/login']);
            }

            return new RedirectResponse('/login');
        }

        return new HtmlResponse($this->template->render('user::registration'));
    }
}
