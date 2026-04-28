-- =============================================================================
-- manifest
-- One row per incoming DC shipment received at a store.
-- Depends on: store, user
-- =============================================================================
DROP TABLE IF EXISTS manifest;
CREATE TABLE IF NOT EXISTS manifest (
    id            INT UNSIGNED      AUTO_INCREMENT,
    store_id      SMALLINT UNSIGNED NOT NULL,
    reference     VARCHAR(100)      NULL COMMENT 'DC manifest / bill-of-lading reference',
    received_date DATE              NOT NULL,
    created_by    INT UNSIGNED      NOT NULL,
    created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_manifest_store (store_id),
    CONSTRAINT fk_manifest_store      FOREIGN KEY (store_id)   REFERENCES store (store_number),
    CONSTRAINT fk_manifest_created_by FOREIGN KEY (created_by) REFERENCES `user`  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
