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

namespace User\Entity;

use DateTimeImmutable;
use Mezzio\Authentication\UserInterface;
use Override;

use function array_merge;
use function array_values;

final class User implements UserInterface
{
    public string $displayName {
        get => $this->firstName . ' ' . $this->lastName;
    }

    public function __construct(
        public readonly int $id,
        public readonly int $storeId,
        public readonly int $roleId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly bool $active,
        public readonly DateTimeImmutable $createdAt,
        public readonly ?string $verificationToken = null,
        public readonly ?DateTimeImmutable $tokenCreatedAt = null,
        /** @var string[] */
        private readonly array $roles = [],
        /** @var array<string, mixed> */
        private readonly array $details = [],
    ) {}

    #[Override]
    public function getIdentity(): string
    {
        return $this->email;
    }

    /** @return string[] */
    #[Override]
    public function getRoles(): array
    {
        return $this->roles;
    }

    /** @param mixed $default */
    #[Override]
    public function getDetail(string $name, $default = null): mixed
    {
        return $this->details[$name] ?? $default;
    }

    /** @return array<string, mixed> */
    #[Override]
    public function getDetails(): array
    {
        return $this->details;
    }

    public function withStoreId(int $storeId): self
    {
        return new self(
            $this->id,
            $storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withRoleId(int $roleId): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withFirstName(string $firstName): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withLastName(string $lastName): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withEmail(string $email): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withPasswordHash(string $passwordHash): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withActive(bool $active): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    /** @param string[] $roles */
    public function withRoles(array $roles): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            array_values($roles),
            $this->details,
        );
    }

    public function withDetail(string $name, mixed $value): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            array_merge($this->details, [$name => $value]),
        );
    }
}
