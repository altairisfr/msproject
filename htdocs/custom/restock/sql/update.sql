ALTER TABLE llx_restockdet ADD COLUMN fk_product	integer NOT NULL DEFAULT 0 after date_end_planned;

ALTER TABLE llx_commandedet ADD COLUMN fk_commandefourndet	integer DEFAULT NULL after import_key;  
-- if something wrong with this value please use following script
-- ALTER TABLE llx_commandedet MODIFY COLUMN fk_commandefourndet integer DEFAULT NULL;