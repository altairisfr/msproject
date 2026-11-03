CREATE TABLE llx_labqcpro_specification_line (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    fk_specification integer NOT NULL,
    position integer NOT NULL DEFAULT 0,
    parameter varchar(255) NOT NULL,
    method varchar(255),
    criteria_type varchar(20) NOT NULL DEFAULT 'range',
    min_value double,
    max_value double,
    target_text varchar(255),
    unit varchar(64),
    mandatory smallint NOT NULL DEFAULT 1,
    datec datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer
) ENGINE=innodb;
