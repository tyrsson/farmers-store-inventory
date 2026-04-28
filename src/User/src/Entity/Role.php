<?php

declare(strict_types=1);

namespace User\Entity;

final readonly class Role
{

    public function __construct(
        public int $id,
        public string $roleId,
    ) {}
}
