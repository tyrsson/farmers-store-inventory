-- =============================================================================
-- acl_privilege
-- Named privileges scoped to a resource.
-- privilege_id is the canonical string passed to AclInterface::isAllowed().
-- label is the human-readable display name (UI / future i18n).
-- Depends on: acl_resource
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_privilege;
CREATE TABLE IF NOT EXISTS acl_privilege (
    privilege_pk  SMALLINT UNSIGNED AUTO_INCREMENT,
    resource_pk   SMALLINT UNSIGNED NOT NULL,
    privilege_id  VARCHAR(100)      NOT NULL COMMENT 'Laminas ACL privilege ID, e.g. create',
    label         VARCHAR(100)      NOT NULL COMMENT 'Display label for management UI',
    PRIMARY KEY (privilege_pk),
    UNIQUE KEY uq_resource_privilege (resource_pk, privilege_id),
    CONSTRAINT fk_priv_resource FOREIGN KEY (resource_pk) REFERENCES acl_resource (resource_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
