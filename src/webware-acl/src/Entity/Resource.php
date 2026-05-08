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

use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Represents a row from the `acl_resource` table.
 * Implements Laminas ResourceInterface so it can be passed directly to Acl::addResource().
 */
final readonly class Resource implements ResourceInterface
{
    public function __construct(
        public int $resourcePk,
        public string $resourceId,
        public string $label,
    ) {}

    public function getResourceId(): string
    {
        return $this->resourceId;
    }
}
