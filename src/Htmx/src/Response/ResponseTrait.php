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

namespace Htmx\Response;

use Htmx\TriggerTrait;

use function json_encode;

trait ResponseTrait
{
    use TriggerTrait;

    private array $allowedKeys = [
        '',
    ];

    public function htmxLocation(string $path, ?string $target = null): void
    {
        if ($target !== null) {
            $this->headers[Header::Location->value] = json_encode(['path' => $path, 'target' => $target]);

            return;
        }
        $this->headers[Header::Location->value] = $path;
    }
}
