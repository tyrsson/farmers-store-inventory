<?php

declare(strict_types=1);

namespace User\Entity;

use DateTimeImmutable;
use Mezzio\Authentication\UserInterface;
use Override;

use function array_merge;
use function array_values;

final readonly class User implements UserInterface
{
    public function __construct(
        public int $id,
        public int $storeId,
        public int $roleId,
        public string $displayName,
        public string $email,
        public string $passwordHash,
        public bool $active,
        public DateTimeImmutable $createdAt,
        /** @var string[] */
        private array $roles = [],
        /** @var array<string, mixed> */
        private array $details = [],
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
            $this->displayName,
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
            $this->displayName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            $this->details,
        );
    }

    public function withDisplayName(string $displayName): self
    {
        return new self(
            $this->id,
            $this->storeId,
            $this->roleId,
            $displayName,
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
            $this->displayName,
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
            $this->displayName,
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
            $this->displayName,
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
            $this->displayName,
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
            $this->displayName,
            $this->email,
            $this->passwordHash,
            $this->active,
            $this->createdAt,
            $this->roles,
            array_merge($this->details, [$name => $value]),
        );
    }
}
