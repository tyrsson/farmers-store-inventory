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

trait TriggerTrait
{
    final public const SYSTEM_MESSAGE = 'systemMessage';

    public function htmxTrigger(
        array $data,
        ?string $event = self::SYSTEM_MESSAGE,
        Header $header = Header::TriggerAfterSettle,
    ): void {
        $this->headers[$header->value] = json_encode([$event => $data]);
    }
}
