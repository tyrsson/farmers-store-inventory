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

namespace Webware\Acl\Admin;

/**
 * Backed string enum whose values serve as PSR-7 request attribute name keys.
 * Middleware sets the attribute; handlers read it to determine write outcome.
 */
enum WriteResult: string
{
    /** Attribute name used when a write operation succeeded. */
    case Success = 'webware_acl.write_result.success';

    /** Attribute name used when a write operation failed. */
    case Failure = 'webware_acl.write_result.failure';
}
