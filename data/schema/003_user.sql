-- =============================================================================
-- user
-- Each user belongs to exactly one store and has one role.
-- Depends on: store, role
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
    id            INT UNSIGNED      AUTO_INCREMENT,
    store_id      SMALLINT UNSIGNED NOT NULL,
    role_id       TINYINT UNSIGNED  NOT NULL,
    first_name    VARCHAR(75)        NOT NULL,
    last_name     VARCHAR(75)        NOT NULL,
    email         VARCHAR(255)      NOT NULL,
    password_hash      VARCHAR(255)      NOT NULL COMMENT 'bcrypt hash; never store plain text',
    active             TINYINT(1)        NOT NULL DEFAULT 0,
    verification_token VARCHAR(36)        NULL,
    token_created_at   DATETIME          NULL,
    created_at         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_email (email),
    CONSTRAINT fk_user_store FOREIGN KEY (store_id) REFERENCES store (store_number),
    CONSTRAINT fk_user_role  FOREIGN KEY (role_id)  REFERENCES role  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
