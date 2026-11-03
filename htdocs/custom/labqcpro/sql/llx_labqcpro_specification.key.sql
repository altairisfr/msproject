ALTER TABLE llx_labqcpro_specification ADD UNIQUE INDEX uk_labqcpro_specification_ref (entity, ref);
ALTER TABLE llx_labqcpro_specification ADD INDEX idx_labqcpro_specification_product (fk_product);
ALTER TABLE llx_labqcpro_specification ADD INDEX idx_labqcpro_specification_status (status);
