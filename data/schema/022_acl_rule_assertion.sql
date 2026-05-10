-- =============================================================================
-- acl_rule_assertion
-- Stores AssertionInterface implementations attached to an acl_rule row.
-- A rule may have zero, one, or many assertions. When more than one assertion
-- is present, AclBuilder wraps them in an AssertionAggregate.
--
-- mode: controls AssertionAggregate evaluation when > 1 row exists per rule_pk.
--   'all'           → ALL assertions must pass (AND)
--   'at_least_one'  → ANY assertion must pass (OR)
-- The mode is per-rule (all rows for a rule_pk share the same mode).
--
-- Depends on: acl_rule
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS acl_rule_assertion;
CREATE TABLE IF NOT EXISTS acl_rule_assertion (
    id          INT UNSIGNED AUTO_INCREMENT,
    rule_pk     INT UNSIGNED         NOT NULL,
    assertion   VARCHAR(255)         NOT NULL COMMENT 'Fully-qualified class name of the AssertionInterface implementation',
    mode        ENUM('all','at_least_one') NOT NULL DEFAULT 'all' COMMENT 'AssertionAggregate mode; ignored when only one assertion exists for the rule',
    sort_order  TINYINT UNSIGNED     NOT NULL DEFAULT 0 COMMENT 'Evaluation order within the aggregate',
    PRIMARY KEY (id),
    KEY idx_assertion_rule_pk (rule_pk),
    CONSTRAINT fk_assertion_rule FOREIGN KEY (rule_pk) REFERENCES acl_rule (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
