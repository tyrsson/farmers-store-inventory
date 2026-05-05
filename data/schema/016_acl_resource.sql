-- =============================================================================
-- acl_resource
-- Named logical domains used as Laminas ACL resource identifiers.
-- resource_id is the canonical string passed to AclInterface::isAllowed().
-- label is the human-readable display name (UI / future i18n).
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_resource;
CREATE TABLE IF NOT EXISTS acl_resource (
    resource_pk  SMALLINT UNSIGNED AUTO_INCREMENT,
    resource_id  VARCHAR(100)      NOT NULL COMMENT 'Laminas ACL resource ID, e.g. ManifestManager',
    label        VARCHAR(100)      NOT NULL COMMENT 'Display label for management UI',
    PRIMARY KEY (resource_pk),
    UNIQUE KEY uq_resource_id (resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
