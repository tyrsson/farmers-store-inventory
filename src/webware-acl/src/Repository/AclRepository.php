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
use PhpDb\Sql\Sql;
use PhpDb\TableGateway\TableGateway;
use Webware\Acl\Entity\Privilege;
use Webware\Acl\Entity\Resource;
use Webware\Acl\Entity\Role;

final class AclRepository implements AclRepositoryInterface
{
    private readonly TableGateway $roles;
    private readonly TableGateway $resources;
    private readonly TableGateway $privileges;
    private readonly TableGateway $rules;
    private readonly TableGateway $routeMappings;
    private readonly TableGateway $version;

    public function __construct(private readonly AdapterInterface $adapter)
    {
        $this->roles         = new TableGateway('role', $adapter);
        $this->resources     = new TableGateway('acl_resource', $adapter);
        $this->privileges    = new TableGateway('acl_privilege', $adapter);
        $this->rules         = new TableGateway('acl_rule', $adapter);
        $this->routeMappings = new TableGateway('acl_route_privilege', $adapter);
        $this->version       = new TableGateway('acl_version', $adapter);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function fetchRoles(): array
    {
        $sql    = $this->roles->getSql();
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
        $sql    = new Sql($this->adapter);
        $select = $sql->select('acl_role_parent')->order('role_pk ASC');

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
        $sql    = $this->resources->getSql();
        $select = $sql->select()->order('resource_pk ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(int) $row['resource_pk']] = new Resource(
                resourcePk: (int) $row['resource_pk'],
                resourceId: (string) $row['resource_id'],
                label:      (string) $row['label'],
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
        $sql    = $this->privileges->getSql();
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
            ->columns(['type'])
            ->order('acl_rule.id ASC');

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[] = [
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
    public function fetchRouteMappings(): array
    {
        $sql    = new Sql($this->adapter);
        $select = $sql->select('acl_route_privilege')
            ->join(
                'acl_resource',
                'acl_resource.resource_pk = acl_route_privilege.resource_pk',
                ['resource_id'],
            )
            ->join(
                'acl_privilege',
                'acl_privilege.privilege_pk = acl_route_privilege.privilege_pk',
                ['privilege_id'],
            )
            ->columns(['route_name']);

        $result = [];
        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            $result[(string) $row['route_name']] = [
                'resource_id'  => (string) $row['resource_id'],
                'privilege_id' => (string) $row['privilege_id'],
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
        $sql    = $this->version->getSql();
        $select = $sql->select()->columns(['version'])->where(['id' => 1]);

        foreach ($sql->prepareStatementForSqlObject($select)->execute() as $row) {
            return (int) $row['version'];
        }

        return 0;
    }
}
