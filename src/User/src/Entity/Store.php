<?php

declare(strict_types=1);

namespace User\Entity;

final readonly class Store
{
    public function __construct(
        public int $storeNumber,
        public string $city,
        public string $state,
        public string $pqaEmail,
    ) {}
}
