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

/**
 * Represents a row from the `acl_privilege` table.
 * privilege_id is the string passed as the third argument to Acl::isAllowed().
 */
final readonly class Privilege
{
    public function __construct(
        public int $privilegePk,
        public int $resourcePk,
        public string $privilegeId,
        public string $label,
    ) {}
}
