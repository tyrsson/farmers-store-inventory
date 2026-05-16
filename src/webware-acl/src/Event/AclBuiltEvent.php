<?php

declare(strict_types=1);

/**
 * This file is part of the Webware\Acl package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Acl\Event;

use Laminas\Permissions\Acl\Acl;

/**
 * Fired after the Acl is fully built.
 * Listeners may add last-minute rules to the Acl instance.
 */
final class AclBuiltEvent
{
    public function __construct(
        public readonly Acl $acl,
    ) {}
}
