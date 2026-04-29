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

namespace Htmx\Request;

use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Laminas\Diactoros\ServerRequestFilter\FilterUsingXForwardedHeaders;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestFilter implements FilterServerRequestInterface
{
    public function __invoke(ServerRequestInterface $request): ServerRequestInterface
    {
        // maintain default behavior
        $request = FilterUsingXForwardedHeaders::trustReservedSubnets()($request);

        $headers     = $request->getHeaders();
        $htmxHeaders = array_flip(Header::toArray(
            normalize: true,
            valueTreatment: 'strtolower',
        ));

        foreach ($headers as $header => $value) {
            if (isset($htmxHeaders[$header])) {
                $request = $request->withAttribute(
                    $header,
                    $value[0] === 'true' ? true : $value[0]
                );
            }
        }

        return $request;
    }
}
