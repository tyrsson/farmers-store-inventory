-- ACL cache version counter.
-- A single row is incremented on every write to any ACL management table.
-- FileAclCache compares the stored version against this value to decide
-- whether the cached raw-array file is still valid.
CREATE TABLE IF NOT EXISTS acl_version (
    id      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    version INT UNSIGNED     NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO acl_version (id, version) VALUES (1, 0);
