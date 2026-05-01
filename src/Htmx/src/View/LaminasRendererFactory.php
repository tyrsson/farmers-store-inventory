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

namespace Htmx\View;

use Laminas\View\HelperPluginManagerInterface;
use Laminas\View\Renderer\RendererInterface;
use Psr\Container\ContainerInterface;

use function array_filter;
use function assert;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function reset;

/**
 * Create and return a LaminasView template instance.
 *
 * This factory works on the basis that laminas-view is correctly configured, and we can retrieve
 * Laminas\View\View from the container along with our own namespaced path stack resolver.
 *
 * A configuration array is expected with the key `config`, the structure of which is
 * documented in {@link ConfigProvider}.
 *
 * @internal
 *
 * @psalm-internal Mezzio\LaminasView
 * @psalm-internal MezzioTest\LaminasView
 */
final class LaminasRendererFactory
{
    public function __invoke(ContainerInterface $container): LaminasRenderer
    {
        /** @psalm-var mixed $config */
        $config = $container->has('config') ? $container->get('config') : [];
        $config = is_iterable($config) ? iterator_to_array($config) : [];

        /**
         * Fetch the default layout from configuration
         *
         * Several locations have evolved for fetching the default layout template name:
         *
         * templates.layout
         * templates.default_layout
         * view_manager.default_layout
         */
        $layouts = array_filter([
            $config['templates']['layout']            ?? null,
            $config['templates']['body']              ?? null,
            $config['templates']['default_layout']    ?? null,
            $config['templates']['default_body']      ?? null,
            $config['view_manager']['default_layout'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');

        $layout = reset($layouts);
        $layout = $layout !== false ? $layout : null;
        assert(is_string($layout) || $layout === null);

        $body = end($layouts);
        $body = $body !== false ? $body : null;
        assert(is_string($body) || $body === null);

        return new LaminasRenderer(
            $container->get(RendererInterface::class),
            $container->get(HelperPluginManagerInterface::class),
            $layout,
            $body,
        );
    }
}
