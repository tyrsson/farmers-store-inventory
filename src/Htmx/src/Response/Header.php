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

use Htmx\EnumTrait;

/**
 * @see https://htmx.org/reference/#response_headers
 */
enum Header: string
{
    use EnumTrait;

    case Location           = 'HX-Location';
    case PushUrl            = 'HX-Push-Url';
    case Redirect           = 'HX-Redirect';
    case Refresh            = 'HX-Refresh';
    case ReplaceUrl         = 'HX-Replace-Url';
    case Reswap             = 'HX-Reswap';
    case Retarget           = 'HX-Retarget';
    case Reselect           = 'HX-Reselect';
    case Trigger            = 'HX-Trigger';
    case TriggerAfterSettle = 'HX-Trigger-After-Settle';
    case TriggerAfterSwap   = 'HX-Trigger-After-Swap';
}
