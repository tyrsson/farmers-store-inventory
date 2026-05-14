<?php

declare(strict_types=1);

namespace Ims\Migration;

use Ims\Migration\Column\Enum;
use Ims\Migration\Column\TinyInteger;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Ddl\Column\Integer;
use PhpDb\Sql\Ddl\Column\Varchar;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\Index\Index;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Literal;
use PhpDb\Sql\Sql;

final class Migration022AclRuleAssertion implements MigrationInterface
{
    public function getStep(): int
    {
        return 22;
    }

    public function getDescription(): string
    {
        return 'Create acl_rule_assertion table';
    }

    public function up(AdapterInterface $adapter): void
    {
        $sql    = new Sql($adapter);
        $create = new CreateTable('acl_rule_assertion');
        $create->ifNotExists();

        $create->addColumn(
            (new Integer('id', nullable: false))
                ->setOptions(['unsigned' => true, 'autoincrement' => true])
        );

        $create->addColumn(
            (new Integer('rule_pk', nullable: false))
                ->setOptions(['unsigned' => true])
        );

        $create->addColumn(
            (new Varchar('assertion', 255))
                ->setOptions(['comment' => 'Fully-qualified class name of the AssertionInterface implementation'])
        );

        $create->addColumn(
            (new Enum('mode', ['all', 'at_least_one'], nullable: false, default: 'all'))
                ->setOptions(['comment' => 'AssertionAggregate mode; ignored when only one assertion exists for the rule'])
        );

        $create->addColumn(
            (new TinyInteger('sort_order', nullable: false, default: 0))
                ->setOptions(['unsigned' => true, 'comment' => 'Evaluation order within the aggregate'])
        );

        $create->addConstraint(new PrimaryKey('id'));
        $create->addConstraint(new Index('rule_pk', 'idx_assertion_rule_pk'));
        $create->addConstraint(
            new ForeignKey('fk_assertion_rule', 'rule_pk', 'acl_rule', 'id', 'CASCADE')
        );

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
            $sql->buildSqlString((new DropTable('acl_rule_assertion'))->ifExists()),
            AdapterInterface::QUERY_MODE_EXECUTE
        );
    }
}
