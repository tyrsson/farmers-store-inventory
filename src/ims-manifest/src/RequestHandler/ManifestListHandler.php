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

use Ims\Manifest\Repository\ManifestRepositoryInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function max;

final class ManifestListHandler implements RequestHandlerInterface
{
    private const int PAGE_SIZE = 25;

    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly ManifestRepositoryInterface $manifests,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * self::PAGE_SIZE;

        $manifests = $this->manifests->findAll(self::PAGE_SIZE, $offset);
        $total     = $this->manifests->countAll();

        return new HtmlResponse($this->template->render('manifest::list', [
            'manifests' => $manifests,
            'total'     => $total,
            'page'      => $page,
            'pageSize'  => self::PAGE_SIZE,
        ]));
    }
}
