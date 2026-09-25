-- Copyright (C) 2019 Francis Appels <francis.appels@z-application.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_backorder_backorder(
	-- BEGIN MODULEBUILDER FIELDS
	fk_product integer, 
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_commandedet integer NOT NULL, 
	fk_commande integer NOT NULL, 
	ref_client varchar(255), 
	fk_soc integer, 
	fk_projet integer, 
	fk_user_author integer, 
	town varchar(64), 
	zip varchar(64), 
	fk_departement integer, 
	fk_pays integer, 
	date_creation datetime NOT NULL, 
	date_livraison datetime, 
	price double(24,8) DEFAULT 0 COMMENT 'order line price HT with discount', 
	qty double(24,8) DEFAULT 0 COMMENT 'order line qty', 
	eqty double(24,8) DEFAULT 0 COMMENT 'expedition line qty', 
	line_amount double(24,8) DEFAULT 0 COMMENT 'calculated backorder line amount', 
	order_amount double(24,8) DEFAULT 0 COMMENT 'calculated backorder order amount', 
	line_qty double(24,8) DEFAULT 0 COMMENT 'calculated backorder line qty', 
	order_qty double(24,8) DEFAULT 0 COMMENT 'calculated backorder order qty', 
	product_qty double(24,8) DEFAULT 0 COMMENT 'calculated backorder total of product qty', 
	product_stock double(24,8) DEFAULT 0 COMMENT 'product total stock', 
	status smallint DEFAULT 0
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;