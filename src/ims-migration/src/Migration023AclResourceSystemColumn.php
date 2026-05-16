<?php

declare(strict_types=1);

namespace Ims\Migration;

use Ims\Migration\Column\TinyInteger;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Metadata\Source as MysqlMetadata;
use PhpDb\Sql\Ddl\AlterTable;
use PhpDb\Sql\Ddl\Constraint\ForeignKey;
use PhpDb\Sql\Sql;

final class Migration023AclResourceSystemColumn implements MigrationInterface
{
    /** @var array<int, array{table: string, name: string, col: string, ref: string, refcol: string}> */
    private const FK_DEFINITIONS = [
        ['table' => 'acl_privilege',      'name' => 'fk_priv_resource',  'col' => 'resource_pk',  'ref' => 'acl_resource',  'refcol' => 'resource_pk'],
        ['table' => 'acl_rule',           'name' => 'fk_rule_resource',  'col' => 'resource_pk',  'ref' => 'acl_resource',  'refcol' => 'resource_pk'],
        ['table' => 'acl_rule',           'name' => 'fk_rule_privilege', 'col' => 'privilege_pk', 'ref' => 'acl_privilege', 'refcol' => 'privilege_pk'],
        ['table' => 'acl_route_privilege','name' => 'fk_rp_resource',    'col' => 'resource_pk',  'ref' => 'acl_resource',  'refcol' => 'resource_pk'],
        ['table' => 'acl_route_privilege','name' => 'fk_rp_privilege',   'col' => 'privilege_pk', 'ref' => 'acl_privilege', 'refcol' => 'privilege_pk'],
    ];

    public function getStep(): int
    {
        return 23;
    }

    public function getDescription(): string
    {
        return 'acl_resource system column + FK ON DELETE CASCADE chain';
    }

    public function up(AdapterInterface $adapter): void
    {
        $metadata = new MysqlMetadata($adapter);
        $sql      = new Sql($adapter);

        if (! in_array('system', $metadata->getColumnNames('acl_resource'), true)) {
            $addCol = new AlterTable('acl_resource');
            $addCol->addColumn(new TinyInteger(
                'system',
                false,
                0,
                ['unsigned' => true, 'comment' => '1 = seeded system resource; cannot be deleted via UI']
            ));
            $adapter->query($sql->buildSqlString($addCol), AdapterInterface::QUERY_MODE_EXECUTE);
        }

        foreach (self::FK_DEFINITIONS as $fk) {
            // Drop old FK if it exists (any delete rule) so we can re-add with CASCADE.
            if ($this->fkExists($metadata, $fk['table'], $fk['name'])) {
                $drop = new AlterTable($fk['table']);
                $drop->dropConstraint($fk['name']);
                $adapter->query($sql->buildSqlString($drop), AdapterInterface::QUERY_MODE_EXECUTE);
            }

            // Add with ON DELETE CASCADE — safe because we just dropped it, or it never existed.
            $add = new AlterTable($fk['table']);
            $add->addConstraint(new ForeignKey(
                $fk['name'],
                $fk['col'],
                $fk['ref'],
                $fk['refcol'],
                'CASCADE',
                'NO ACTION'
            ));
            $adapter->query($sql->buildSqlString($add), AdapterInterface::QUERY_MODE_EXECUTE);
        }
    }

    public function down(AdapterInterface $adapter): void
    {
        $metadata = new MysqlMetadata($adapter);
        $sql      = new Sql($adapter);

        foreach (self::FK_DEFINITIONS as $fk) {
            if ($this->fkExists($metadata, $fk['table'], $fk['name'])) {
                $drop = new AlterTable($fk['table']);
                $drop->dropConstraint($fk['name']);
                $adapter->query($sql->buildSqlString($drop), AdapterInterface::QUERY_MODE_EXECUTE);
            }

            $add = new AlterTable($fk['table']);
            $add->addConstraint(new ForeignKey(
                $fk['name'],
                $fk['col'],
                $fk['ref'],
                $fk['refcol'],
                'NO ACTION',
                'NO ACTION'
            ));
            $adapter->query($sql->buildSqlString($add), AdapterInterface::QUERY_MODE_EXECUTE);
        }
    }

    private function fkExists(MetadataInterface $metadata, string $table, string $constraintName): bool
    {
        foreach ($metadata->getConstraints($table) as $constraint) {
            if ($constraint->isForeignKey() && $constraint->getName() === $constraintName) {
                return true;
            }
        }

        return false;
    }
}
