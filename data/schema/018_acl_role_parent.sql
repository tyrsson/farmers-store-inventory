-- =============================================================================
-- acl_role_parent
-- Role inheritance tree. A role may inherit from one or more parent roles.
-- Laminas ACL resolves inherited permissions depth-first, highest-priority
-- parent (most recently added) checked first.
-- Depends on: role
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_role_parent;
CREATE TABLE IF NOT EXISTS acl_role_parent (
    role_pk    TINYINT UNSIGNED NOT NULL COMMENT 'Child role',
    parent_pk  TINYINT UNSIGNED NOT NULL COMMENT 'Parent role this role inherits from',
    PRIMARY KEY (role_pk, parent_pk),
    CONSTRAINT fk_arp_role   FOREIGN KEY (role_pk)   REFERENCES role (id),
    CONSTRAINT fk_arp_parent FOREIGN KEY (parent_pk) REFERENCES role (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
