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

namespace Webware\Acl\Entity;

use Laminas\Permissions\Acl\Role\RoleInterface;

/**
 * Represents a row from the `role` table.
 * Implements Laminas RoleInterface so it can be passed directly to Acl::addRole().
 */
final readonly class Role implements RoleInterface
{
    public function __construct(
        public int $id,
        public string $roleId,
    ) {}

    public function getRoleId(): string
    {
        return $this->roleId;
    }
}
