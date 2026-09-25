-- Copyright (C) 2020-2022		Charlene BENKE		<charlene@patas-monkey.com>
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
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_restockdet(
	rowid 				integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	fk_restock 			integer NOT NULL, 
	fk_elementid 		integer NOT NULL,
	element_type 		varchar(32) NULL, 	-- 'order', 'proposal', 'factory'
	description 		text, 
	progress			integer DEFAULT 0,	-- pourcentage d'avancement de l'élément
	date_end_planned 	datetime NULL, 	-- date de fin prévue de l'élément 
	fk_product 			integer NOT NULL DEFAULT 0, 
	quantity			double DEFAULT 0,	-- quantité d'éléments associé à l'élénent (utile pour factory)
	tms 				timestamp, 
	import_key 			varchar(14) 
) ENGINE=innodb;
