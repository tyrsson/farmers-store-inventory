-- =============================================================================
-- acl_rule
-- Unified allow/deny rules: grants or revokes a privilege on a resource
-- for a given role. AclBuilder iterates these rows and calls
-- Acl::allow() or Acl::deny() accordingly.
-- Depends on: role, acl_resource, acl_privilege
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_rule;
CREATE TABLE IF NOT EXISTS acl_rule (
    id            INT UNSIGNED AUTO_INCREMENT,
    role_pk       TINYINT UNSIGNED     NOT NULL,
    resource_pk   SMALLINT UNSIGNED    NOT NULL,
    privilege_pk  SMALLINT UNSIGNED    NOT NULL,
    type          ENUM('allow','deny') NOT NULL DEFAULT 'allow',
    PRIMARY KEY (id),
    UNIQUE KEY uq_rule (role_pk, resource_pk, privilege_pk),
    CONSTRAINT fk_rule_role      FOREIGN KEY (role_pk)      REFERENCES role          (id),
    CONSTRAINT fk_rule_resource  FOREIGN KEY (resource_pk)  REFERENCES acl_resource  (resource_pk),
    CONSTRAINT fk_rule_privilege FOREIGN KEY (privilege_pk) REFERENCES acl_privilege (privilege_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
