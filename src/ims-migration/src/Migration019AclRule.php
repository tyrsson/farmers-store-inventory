<?php

declare(strict_types=1);

namespace Ims\Migration;

use Ims\Migration\Column\Enum;
use Ims\Migration\Column\TinyInteger;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Json;
use PhpDb\Sql\Ddl\Column\SmallInteger;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Constraint\UniqueKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration019AclRule implements MigrationInterface
{
    public function getStep(): int
    {
        return 19;
    }

    public function getDescription(): string
    {
        return 'Create acl_rule table';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_rule');
        $create->ifNotExists();

        $create->addColumn(
            (new Integer('id', nullable: false))
                ->setOptions(['unsigned' => true, 'autoincrement' => true])
        );

        $create->addColumn(
            (new TinyInteger('role_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            (new SmallInteger('resource_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            (new SmallInteger('privilege_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            new Enum('type', ['allow', 'deny'], nullable: false, default: 'allow')
        );

        $create->addColumn(
            (new Json('params', nullable: true))
                ->setOptions(['comment' => 'Plugin extension data'])
        );

        $create->addConstraint(new PrimaryKey('id'));
        $create->addConstraint(new UniqueKey(['role_pk', 'resource_pk', 'privilege_pk'], 'uq_rule'));
        $create->addConstraint(new ForeignKey('fk_rule_role', 'role_pk', 'role', 'id'));
        $create->addConstraint(new ForeignKey('fk_rule_resource', 'resource_pk', 'acl_resource', 'resource_pk', 'CASCADE'));
        $create->addConstraint(new ForeignKey('fk_rule_privilege', 'privilege_pk', 'acl_privilege', 'privilege_pk', 'CASCADE'));

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
            $sql->buildSqlString((new DropTable('acl_rule'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
