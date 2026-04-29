<?php

declare(strict_types=1);

namespace User\Repository;

use DateTimeImmutable;
use Mezzio\Authentication\UserInterface;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\TableGateway;
use User\Entity\User;

use function password_verify;

final class UserRepository implements UserRepositoryInterface
{
    private readonly TableGateway $gateway;

    public function __construct(
        AdapterInterface $adapter,
        /** @var callable(string, string[], array<string,mixed>): UserInterface */
        private readonly mixed $userFactory,
    ) {
        $this->gateway = new TableGateway('user', $adapter);
    }

    public function authenticate(string $credential, ?string $password = null): ?UserInterface
    {
        $user = $this->findByEmail($credential);

        if ($user === null || ! $user->active) {
            return null;
        }

        if (! password_verify($password ?? '', $user->passwordHash)) {
            return null;
        }

        return ($this->userFactory)(
            $user->getIdentity(),
            $user->getRoles(),
            $user->getDetails(),
        );
    }

    public function findByEmail(string $email): ?User
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()
            ->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
            ->where(['user.email' => $email])
            ->limit(1);

        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        if ($row === null) {
            return null;
        }

        return $this->hydrate((array) $row);
    }

    public function findById(int $id): ?User
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()
            ->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
            ->where(['user.id' => $id])
            ->limit(1);

        $row = $sql->prepareStatementForSqlObject($select)->execute()->current();
        if ($row === null) {
            return null;
        }

        return $this->hydrate((array) $row);
    }

    /** @return User[] */
    public function findAll(?int $storeId = null): array
    {
        $sql    = $this->gateway->getSql();
        $select = $sql->select()
            ->join('role', 'role.id = user.role_id', ['role_name' => 'role_id'])
            ->order('user.last_name ASC');

        if ($storeId !== null) {
            $select->where(['user.store_id' => $storeId]);
        }

        $users = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $users[] = $this->hydrate((array) $row);
        }

        return $users;
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $sql    = $this->gateway->getSql();
        $insert = $sql->insert()->values($data);

        $sql->prepareStatementForSqlObject($insert)->execute();

        return (int) $this->gateway->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $sql    = $this->gateway->getSql();
        $update = $sql->update()->set($data)->where(['id' => $id]);

        $sql->prepareStatementForSqlObject($update)->execute();
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        return new User(
            id:           (int) $row['id'],
            storeId:      (int) $row['store_id'],
            roleId:       (int) $row['role_id'],
            firstName:    (string) $row['first_name'],
            lastName:     (string) $row['last_name'],
            email:        (string) $row['email'],
            passwordHash: (string) $row['password_hash'],
            active:       (bool) $row['active'],
            createdAt:    new DateTimeImmutable((string) $row['created_at']),
            roles:        [(string) $row['role_name']],
            details:      [
                'store_id' => (int) $row['store_id'],
            ],
        );
    }
}
