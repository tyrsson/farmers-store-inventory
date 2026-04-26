<?php

declare(strict_types=1);

/**
 * This file is part of the Tyrsson Webinertia package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Htmx;

use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Mezzio\Template\TemplateRendererInterface;

/**
 * @internal
 */
final readonly class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'aliases'   => [
                FilterServerRequestInterface::class => Request\ServerRequestFilter::class,
                TemplateRendererInterface::class    => View\LaminasRenderer::class,
            ],
            'factories' => [
                Middleware\DetectAjaxRequestMiddleware::class => Middleware\DetectAjaxRequestMiddlewareFactory::class,
                View\LaminasRenderer::class                   => View\LaminasRendererFactory::class,
            ],
        ];
    }

    private function getTemplates(): array
    {
        return [
            'map'          => [
                'body::default' => __DIR__ . '/../templates/body/default.phtml',
            ],
            'default_body' => 'body::default',
        ];
    }
}
