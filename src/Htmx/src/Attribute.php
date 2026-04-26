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

enum Attribute: string
{
    /**
     * @see https://htmx.org/reference/#attributes
     */
    case Get       = 'hx-get';
    case Post      = 'hx-post';
    case On        = 'hx-on';
    case Push_Url  = 'hx-push-url';
    case Select    = 'hx-select';
    case SelectOob = 'hx-select-oob';
    case Swap      = 'hx-swap';
    case SwapOob   = 'hx-swap-oob';
    case Target    = 'hx-target';
    case Trigger   = 'hx-trigger';
    case Vals      = 'hx-vals';

    /**
     * @see https://htmx.org/reference/#attributes-additional
     */
    case Boost       = 'hx-boost';
    case Confirm     = 'hx-confirm';
    case Delete      = 'hx-delete';
    case Disable     = 'hx-disable';
    case DisableElt  = 'hx-disable-elt';
    case Disinherit  = 'hx-disinherit';
    case Encoding    = 'hx-encoding';
    case Ext         = 'hx-ext';
    case Headers     = 'hx-headers';
    case History     = 'hx-history';
    case HistoryElt  = 'hx-history-elt';
    case Include     = 'hx-include';
    case Indicator   = 'hx-indicator';
    case Inherit     = 'hx-inherit';
    case Params      = 'hx-params';
    case Patch       = 'hx-patch';
    case Preserve    = 'hx-preserve';
    case Prompt      = 'hx-prompt';
    case Put         = 'hx-put';
    case Replace_Url = 'hx-replace-url';
    case Request     = 'hx-request';
    case Sync        = 'hx-sync';
    case Validate    = 'hx-validate';
    case Vars        = 'hx-vars';
}
