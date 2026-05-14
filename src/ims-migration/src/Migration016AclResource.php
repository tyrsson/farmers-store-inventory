<?php

declare(strict_types=1);

namespace Ims\Migration;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration016AclResource implements MigrationInterface
{
    public function getStep(): int
    {
        return 16;
    }

    public function getDescription(): string
    {
        return 'Create acl_resource table';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_resource');
        $create->ifNotExists();

        $create->addColumn(
            (new SmallInteger('resource_pk', nullable: false))
                ->setOptions(['unsigned' => true, 'autoincrement' => true])
        );

        $create->addColumn(
            (new Varchar('resource_id', 100))
                ->setOptions(['comment' => 'Laminas ACL resource ID, e.g. ManifestManager'])
        );

        $create->addColumn(
            (new Varchar('label', 100))
                ->setOptions(['comment' => 'Display label for management UI'])
        );

        $create->addColumn(
            (new Json('params', nullable: true))
                ->setOptions(['comment' => 'Plugin extension data'])
        );

        $create->addConstraint(new PrimaryKey('resource_pk'));
        $create->addConstraint(new UniqueKey('resource_id', 'uq_resource_id'));

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
            $sql->buildSqlString((new DropTable('acl_resource'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
