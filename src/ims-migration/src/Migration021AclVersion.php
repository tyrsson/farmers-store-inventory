<?php

declare(strict_types=1);

namespace Ims\Migration;

use Ims\Migration\Column\TinyInteger;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration021AclVersion implements MigrationInterface
{
    public function getStep(): int
    {
        return 21;
    }

    public function getDescription(): string
    {
        return 'Create acl_version table and seed initial row';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_version');
        $create->ifNotExists();

        $create->addColumn(
            (new TinyInteger('id', nullable: false, default: 1))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            (new Integer('version', nullable: false, default: 0))
                ->setOptions(['unsigned' => true])
        );

        $create->addConstraint(new PrimaryKey('id'));

        $create->setOptions([
            'engine'          => new Literal('InnoDB'),
            'default charset' => new Literal('utf8mb4'),
            'collate'         => new Literal('utf8mb4_unicode_ci'),
        ]);

        $adapter->query(
            $sql->buildSqlString($create),
            AdapterInterface::QUERY_MODE_EXECUTE
        );

        // Seed the single tracking row — idempotent via INSERT IGNORE.
        $adapter->query(
            'INSERT IGNORE INTO acl_version (id, version) VALUES (1, 0)',
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }

    public function down(AdapterInterface $adapter): void
    {
        $sql = new Sql($adapter);

        $adapter->query(
            $sql->buildSqlString((new DropTable('acl_version'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
