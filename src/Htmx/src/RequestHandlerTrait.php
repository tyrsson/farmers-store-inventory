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

use Htmx\Response\Header;

use function json_encode;

trait RequestHandlerTrait
{
    private string $domTarget = 'main';

    private function hxLocation(array $params): array
    {
        $data = ['target' => $this->domTarget];
        $data = $params + $data;

        return [Header::Location->value => json_encode($data)];
    }
}
