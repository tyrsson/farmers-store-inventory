-- Adds the `system` flag column to acl_resource.
-- Single statement only — db-seed executes one statement per file.
--
-- ON DELETE CASCADE FK chain is handled by the PHP migration class
-- (data/migrations/Migration023AclResourceSystemColumn.php) via bin/migrate,
-- which executes each ALTER TABLE as a separate query.

ALTER TABLE `acl_resource`
    ADD COLUMN IF NOT EXISTS `system` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
        COMMENT '1 = seeded system resource; cannot be deleted via UI'
    AFTER `label`;
