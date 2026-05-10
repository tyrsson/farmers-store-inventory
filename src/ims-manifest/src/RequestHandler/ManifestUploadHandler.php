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

namespace Ims\Manifest\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\Acl\Admin\WriteResult;

final class ManifestUploadHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // After a successful POST the middleware sets WriteResult::Success and manifest_id
        if ($request->getAttribute(WriteResult::Success->value) === true) {
            $manifestId = (int) $request->getAttribute('manifest_id');
            return new RedirectResponse('/manifests/' . $manifestId);
        }

        // GET or failed POST — render the upload form
        return new HtmlResponse($this->template->render('manifest::upload'));
    }
}

