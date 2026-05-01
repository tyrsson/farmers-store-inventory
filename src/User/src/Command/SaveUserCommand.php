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

namespace User\Command;

use Webware\CommandBus\Command\NamedCommandInterface;
use Webware\CommandBus\Command\NamedCommandTrait;

final readonly class SaveUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
        public int $storeId,
    ) {}
}
