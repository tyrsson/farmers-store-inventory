-- =============================================================================
-- role
-- Roles in ascending privilege order; application enforces ACL rules.
-- role_id uses Title Case with spaces so it is also the display label —
-- no normalisation needed in the UI. Used directly as the Laminas ACL role ID.
-- Valid names: Sales, Warehouse, Warehouse Supervisor, Credit Manager,
--              DC Warehouse, Manager, Administrator
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS role;
CREATE TABLE IF NOT EXISTS role (
    id      TINYINT UNSIGNED AUTO_INCREMENT,
    role_id VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_role_id (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 1;
