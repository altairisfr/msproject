CREATE TABLE llx_labqcpro_specification (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    entity integer NOT NULL DEFAULT 1,
    fk_product integer NOT NULL,
    ref varchar(128) NOT NULL,
    label varchar(255) NOT NULL,
    version varchar(50) DEFAULT '1.0',
    status smallint NOT NULL DEFAULT 0,
    description text,
    datec datetime NOT NULL,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat integer NOT NULL,
    fk_user_modif integer
) ENGINE=innodb;
