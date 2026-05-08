-- =============================================================================
-- acl_route_privilege
-- Maps a named Mezzio route to the resource + privilege the ACL must check.
-- AclMiddleware looks up the matched route_name here to determine what to
-- pass to Acl::isAllowed(). Routes with no row are denied by default.
-- Depends on: acl_resource, acl_privilege
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_route_privilege;
CREATE TABLE IF NOT EXISTS acl_route_privilege (
    id            INT UNSIGNED AUTO_INCREMENT,
    route_name    VARCHAR(200)      NOT NULL COMMENT 'Mezzio route name, e.g. user.login',
    resource_pk   SMALLINT UNSIGNED NOT NULL,
    privilege_pk  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_route_name (route_name),
    CONSTRAINT fk_rp_resource  FOREIGN KEY (resource_pk)  REFERENCES acl_resource  (resource_pk),
    CONSTRAINT fk_rp_privilege FOREIGN KEY (privilege_pk) REFERENCES acl_privilege (privilege_pk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
