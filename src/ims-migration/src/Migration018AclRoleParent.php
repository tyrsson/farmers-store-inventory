<?php

declare(strict_types=1);

namespace Ims\Migration;

use Ims\Migration\Column\TinyInteger;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration018AclRoleParent implements MigrationInterface
{
    public function getStep(): int
    {
        return 18;
    }

    public function getDescription(): string
    {
        return 'Create acl_role_parent table';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_role_parent');
        $create->ifNotExists();

        $create->addColumn(
            (new TinyInteger('role_pk', nullable: false))
                ->setOptions(['unsigned' => true, 'comment' => 'Child role'])
        );

        $create->addColumn(
            (new TinyInteger('parent_pk', nullable: false))
                ->setOptions(['unsigned' => true, 'comment' => 'Parent role this role inherits from'])
        );

        $create->addConstraint(new PrimaryKey(['role_pk', 'parent_pk']));
        $create->addConstraint(new ForeignKey('fk_arp_role', 'role_pk', 'role', 'id'));
        $create->addConstraint(new ForeignKey('fk_arp_parent', 'parent_pk', 'role', 'id'));

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
            $sql->buildSqlString((new DropTable('acl_role_parent'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
