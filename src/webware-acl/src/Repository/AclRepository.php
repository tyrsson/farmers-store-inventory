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

namespace Webware\Acl\Repository;

use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Sql;
use Webware\Acl\Entity\Privilege;
use Webware\Acl\Entity\Resource;
use Webware\Acl\Entity\Role;

final class AclRepository implements AclRepositoryInterface
{
    private ?int $cachedVersion = null;

    public function __construct(private readonly AdapterInterface $adapter)
    {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchRoles(): array
    {
        $sql    = new Sql($this->adapter, 'role');
        $select = $sql->select()->order('id ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['id']] = new Role(
                id:     (int) $row['id'],
                roleId: (string) $row['role_id'],
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchRoleParents(): array
    {
        $sql    = new Sql($this->adapter, 'acl_role_parent');
        $select = $sql->select()->order('role_pk ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['role_pk']][] = (int) $row['parent_pk'];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchResources(): array
    {
        $sql    = new Sql($this->adapter, 'acl_resource');
        $select = $sql->select()->order('resource_pk ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['resource_pk']] = new Resource(
                resourcePk: (int) $row['resource_pk'],
                resourceId: $row['resource_id'],
                label:      $row['label'],
                system:     (int) $row['system'] === 1,
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchPrivileges(): array
    {
        $sql    = new Sql($this->adapter, 'acl_privilege');
        $select = $sql->select()->order('privilege_pk ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['privilege_pk']] = new Privilege(
                privilegePk: (int) $row['privilege_pk'],
                resourcePk:  (int) $row['resource_pk'],
                privilegeId: (string) $row['privilege_id'],
                label:       (string) $row['label'],
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchRules(): array
    {
        $sql    = new Sql($this->adapter);
        $select = $sql->select('acl_rule')
            ->join(
                'role',
                'role.id = acl_rule.role_pk',
                ['role_id'],
            )
            ->join(
                'acl_resource',
                'acl_resource.resource_pk = acl_rule.resource_pk',
                ['resource_id'],
            )
            ->join(
                'acl_privilege',
                'acl_privilege.privilege_pk = acl_rule.privilege_pk',
                ['privilege_id'],
            )
            ->columns(['id', 'type'])
            ->order('acl_rule.id ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['id']] = [
                'id'           => (int) $row['id'],
                'role_id'      => (string) $row['role_id'],
                'resource_id'  => (string) $row['resource_id'],
                'privilege_id' => (string) $row['privilege_id'],
                'type'         => (string) $row['type'],
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchRuleAssertions(): array
    {
        $sql    = new Sql($this->adapter);
        $select = $sql->select('acl_rule_assertion')
            ->columns(['id', 'rule_pk', 'assertion', 'mode', 'sort_order'])
            ->order(['rule_pk ASC', 'sort_order ASC']);

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $rulePk = (int) $row['rule_pk'];
            $result[$rulePk][] = [
                'id'         => (int)    $row['id'],
                'assertion'  => (string) $row['assertion'],
                'mode'       => (string) $row['mode'],
                'sort_order' => (int)    $row['sort_order'],
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchVersion(): int
    {
        if ($this->cachedVersion !== null) {
            return $this->cachedVersion;
        }

        $sql    = new Sql($this->adapter, 'acl_version');
        $select = $sql->select()->columns(['version'])->where(['id' => 1]);

        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            return $this->cachedVersion = (int) $row['version'];
        }

        return $this->cachedVersion = 0;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function insertRole(string $roleId, int $parentPk): int
    {
        $sql    = new Sql($this->adapter, 'role');
        $insert = $sql->insert()->values(['role_id' => $roleId]);
        $sql->prepareStatementForSqlObject($insert)->execute();

        $newPk = (int) $this->adapter->getDriver()->getLastGeneratedValue();

        $parentSql    = new Sql($this->adapter, 'acl_role_parent');
        $parentInsert = $parentSql->insert()->values(['role_pk' => $newPk, 'parent_pk' => $parentPk]);
        $parentSql->prepareStatementForSqlObject($parentInsert)->execute();

        return $newPk;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function saveRole(string $roleId, int $parentPk): int
    {
        $sql    = new Sql($this->adapter, 'role');
        $select = $sql->select()->columns(['id'])->where(['role_id' => $roleId]);
        $existing = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if ($existing !== false && $existing !== null) {
            $pk = (int) $existing['id'];
            $parentSql = new Sql($this->adapter, 'acl_role_parent');
            $update    = $parentSql->update()->set(['parent_pk' => $parentPk])->where(['role_pk' => $pk]);
            $parentSql->prepareStatementForSqlObject($update)->execute();
            return $pk;
        }

        return $this->insertRole($roleId, $parentPk);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function insertResource(string $resourceId, string $label): int
    {
        $sql    = new Sql($this->adapter, 'acl_resource');
        $insert = $sql->insert()->values(['resource_id' => $resourceId, 'label' => $label]);
        $sql->prepareStatementForSqlObject($insert)->execute();

        return (int) $this->adapter->getDriver()->getLastGeneratedValue();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function saveResource(string $resourceId, string $label): int
    {
        $sql      = new Sql($this->adapter, 'acl_resource');
        $select   = $sql->select()->columns(['resource_pk'])->where(['resource_id' => $resourceId]);
        $existing = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if ($existing !== false && $existing !== null) {
            $pk     = (int) $existing['resource_pk'];
            $update = $sql->update()->set(['label' => $label])->where(['resource_pk' => $pk]);
            $sql->prepareStatementForSqlObject($update)->execute();
            return $pk;
        }

        return $this->insertResource($resourceId, $label);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function insertPrivilege(int $resourcePk, string $privilegeId, string $label): int
    {
        $sql      = new Sql($this->adapter, 'acl_privilege');
        $select   = $sql->select()->columns(['privilege_pk'])->where(['resource_pk' => $resourcePk, 'privilege_id' => $privilegeId]);
        $existing = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if ($existing !== false && $existing !== null) {
            return (int) $existing['privilege_pk'];
        }

        $insert = $sql->insert()->values([
            'resource_pk'  => $resourcePk,
            'privilege_id' => $privilegeId,
            'label'        => $label,
        ]);
        $sql->prepareStatementForSqlObject($insert)->execute();

        return (int) $this->adapter->getDriver()->getLastGeneratedValue();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function saveRule(int $rolePk, int $resourcePk, int $privilegePk, string $type): void
    {
        $sql    = new Sql($this->adapter, 'acl_rule');
        $select = $sql->select()
            ->columns(['id'])
            ->where(['role_pk' => $rolePk, 'resource_pk' => $resourcePk, 'privilege_pk' => $privilegePk]);

        $existing = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if ($existing !== false && $existing !== null) {
            $update = $sql->update()->set(['type' => $type])->where(['id' => (int) $existing['id']]);
            $sql->prepareStatementForSqlObject($update)->execute();
        } else {
            $insert = $sql->insert()->values([
                'role_pk'      => $rolePk,
                'resource_pk'  => $resourcePk,
                'privilege_pk' => $privilegePk,
                'type'         => $type,
            ]);
            $sql->prepareStatementForSqlObject($insert)->execute();
        }
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function deleteRole(int $rolePk): void
    {
        // Remove parent-mapping rows first (FK constraint)
        $sql    = new Sql($this->adapter, 'acl_role_parent');
        $delete = $sql->delete()->where(['role_pk' => $rolePk]);
        $sql->prepareStatementForSqlObject($delete)->execute();

        // Remove the role itself
        $sql    = new Sql($this->adapter, 'role');
        $delete = $sql->delete()->where(['id' => $rolePk]);
        $sql->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function deleteResource(int $resourcePk): void
    {
        // The WHERE system = 0 guard is the final safety net against deleting
        // seeded system resources. The UI disables the button for system rows,
        // but this ensures the DB layer never removes them even if called directly.
        // ON DELETE CASCADE (added in 023_acl_resource_system.sql) handles all
        // dependent rows: acl_privilege → acl_rule → acl_rule_assertion and
        // acl_route_privilege atomically.
        $sql    = new Sql($this->adapter, 'acl_resource');
        $delete = $sql->delete()->where(['resource_pk' => $resourcePk, 'system' => 0]);
        $sql->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function deleteRule(int $id): void
    {
        $sql    = new Sql($this->adapter, 'acl_rule');
        $delete = $sql->delete()->where(['id' => $id]);
        $sql->prepareStatementForSqlObject($delete)->execute();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]    public function updateRuleType(int $id, string $type): void
    {
        $sql    = new Sql($this->adapter, 'acl_rule');
        $update = $sql->update()->set(['type' => $type])->where(['id' => $id]);
        $sql->prepareStatementForSqlObject($update)->execute();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function incrementVersion(): void
    {
        $sql    = new Sql($this->adapter, 'acl_version');
        $update = $sql->update()->set(['version' => new Expression('version + 1')])->where(['id' => 1]);
        $sql->prepareStatementForSqlObject($update)->execute();
        $this->cachedVersion = null;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function saveRuleAssertion(int $rulePk, string $assertion, string $mode, int $sortOrder): int
    {
        $sql    = new Sql($this->adapter, 'acl_rule_assertion');
        $insert = $sql->insert()->values([
            'rule_pk'    => $rulePk,
            'assertion'  => $assertion,
            'mode'       => $mode,
            'sort_order' => $sortOrder,
        ]);
        $sql->prepareStatementForSqlObject($insert)->execute();

        return (int) $this->adapter->getDriver()->getLastGeneratedValue();
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function deleteRuleAssertion(int $id): void
    {
        $sql    = new Sql($this->adapter, 'acl_rule_assertion');
        $delete = $sql->delete()->where(['id' => $id]);
        $sql->prepareStatementForSqlObject($delete)->execute();
    }
}
