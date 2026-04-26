-- =============================================================================
-- role
-- Roles in ascending privilege order; application enforces ACL rules.
-- Names use Title Case with spaces so the name column is also the display
-- label — no normalisation needed in the UI.
-- Valid names: Sales, Warehouse, Warehouse Supervisor, Credit Manager,
--              DC Warehouse, Manager
-- =============================================================================
CREATE TABLE IF NOT EXISTS role (
    id   TINYINT UNSIGNED AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_role_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
