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

use App\EnumTrait;

/**
 * @see https://htmx.org/reference/#request_headers
 */
enum Header: string
{
    use EnumTrait;

    case Boosted               = 'hx-boosted';
    case CurrentUrl            = 'hx-current-url';
    case HistoryRestoreRequest = 'hx-history-restore-request';
    case Prompt                = 'hx-prompt';
    case Request               = 'hx-request';
    case Target                = 'hx-target';
    case TriggerName           = 'hx-trigger-name';
    case Trigger               = 'hx-trigger';
}
