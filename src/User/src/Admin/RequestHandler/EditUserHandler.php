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

namespace User\Admin\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Repository\UserRepositoryInterface;

final class UpdateUserHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id   = (int) $request->getAttribute('id');
        $user = $this->users->findById($id);

        return new HtmlResponse($this->template->render('user::edit-user', [
            'user' => $user,
        ]));
    }
}
