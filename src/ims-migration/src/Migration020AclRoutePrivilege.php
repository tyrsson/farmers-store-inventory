<?php

declare(strict_types=1);

namespace Ims\Migration;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration020AclRoutePrivilege implements MigrationInterface
{
    public function getStep(): int
    {
        return 20;
    }

    public function getDescription(): string
    {
        return 'Create acl_route_privilege table';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_route_privilege');
        $create->ifNotExists();

        $create->addColumn(
            (new Integer('id', nullable: false))
                ->setOptions(['unsigned' => true, 'autoincrement' => true])
        );

        $create->addColumn(
            (new Varchar('route_name', 200))
                ->setOptions(['comment' => 'Mezzio route name, e.g. user.login'])
        );

        $create->addColumn(
            (new SmallInteger('resource_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            (new SmallInteger('privilege_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addConstraint(new PrimaryKey('id'));
        $create->addConstraint(new UniqueKey('route_name', 'uq_route_name'));
        $create->addConstraint(new ForeignKey('fk_rp_resource', 'resource_pk', 'acl_resource', 'resource_pk', 'CASCADE'));
        $create->addConstraint(new ForeignKey('fk_rp_privilege', 'privilege_pk', 'acl_privilege', 'privilege_pk', 'CASCADE'));

        $create->setOptions([
            'engine'          => new Literal('InnoDB'),
            'default charset' => new Literal('utf8mb4'),
            'collate'         => new Literal('utf8mb4_unicode_ci'),
        ]);

        $adapter->query(
            $sql->buildSqlString($create),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }

    public function down(AdapterInterface $adapter): void
    {
        $sql = new Sql($adapter);

        $adapter->query(
            $sql->buildSqlString((new DropTable('acl_route_privilege'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
