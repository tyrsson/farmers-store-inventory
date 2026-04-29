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

namespace Htmx;

enum Swap: string
{
    case InnerHTML   = 'innerHTML';
    case OuterHTML   = 'outerHTML';
    case BeforeBegin = 'beforebegin';
    case AfterBegin  = 'afterbegin';
    case BeforeEnd   = 'beforeend';
    case AfterEnd    = 'afterend';
    case Delete      = 'delete';
    case None        = 'none';
}
