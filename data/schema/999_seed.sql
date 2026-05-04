-- =============================================================================
-- Reference data seed — run after all table files (001–014)
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Roles (ordered by ascending privilege level)
-- -----------------------------------------------------------------------------
INSERT INTO role (role_id) VALUES
    ('Sales'),
    ('Warehouse'),
    ('Warehouse Supervisor'),
    ('Credit Manager'),
    ('DC Warehouse'),
    ('Manager'),
    ('Administrator')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- -----------------------------------------------------------------------------
-- Sample stores
-- Replace pqa_email values with real addresses before deploying.
-- -----------------------------------------------------------------------------
INSERT INTO store (store_number, city, state, pqa_email) VALUES
    (207, 'Leeds',      'AL', 'pqa-207@example.com'),
    (112, 'Birmingham', 'AL', 'pqa-112@example.com')
ON DUPLICATE KEY UPDATE
    city      = VALUES(city),
    state     = VALUES(state),
    pqa_email = VALUES(pqa_email);
