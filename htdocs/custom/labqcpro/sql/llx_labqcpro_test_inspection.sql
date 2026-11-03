-- LabQC Pro v1.1.1 — additive optional inspection metadata
CREATE TABLE IF NOT EXISTS llx_labqcpro_test_inspection (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer NOT NULL DEFAULT 1,
    fk_test integer NOT NULL,
    batch_quantity bigint unsigned DEFAULT NULL,
    quantity_inspected bigint unsigned DEFAULT NULL,
    aql_level varchar(20) DEFAULT NULL,
    third_party_reference varchar(255) DEFAULT NULL,
    inspection_type varchar(32) DEFAULT NULL,
    datec datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_modif integer NOT NULL,
    UNIQUE KEY uk_labqcpro_inspection_test (entity, fk_test),
    KEY idx_labqcpro_inspection_test (fk_test)
) ENGINE=innodb;
