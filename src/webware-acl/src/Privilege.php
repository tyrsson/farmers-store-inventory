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

namespace Webware\Acl;

/**
 * Codifies the canonical ACL privilege identifiers used throughout the system.
 *
 * Route names follow the pattern {resource}.{privilege}, so privilege terminal
 * segments must match these string values exactly.
 *
 * Non-instantiable — use constants directly: Privilege::READ, Privilege::CREATE, etc.
 */
final class Privilege
{
    private function __construct() {}

    public const string READ   = 'read';
    public const string CREATE = 'create';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';
}
