<?php
/* Copyright (C) 2022-2024 Nicolas SILOBRE <nsilobre@ns-info.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    bompdf/lib/fctpdf.lib.php
 * \ingroup bompdf
 * \brief   Library files with common functions for Bompdf
 */

function returnWh($fk_product)
{

    global $db;
    $libentrepot = '';

    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
    $product = new Product($db);
    $product->fetch($fk_product);

    $entrepot = new Entrepot($db);
    $entrepot->fetch($product->fk_default_warehouse);
    $libentrepot = $entrepot->ref;
    return $libentrepot;
}

function listoflotbyprodct($idproduct, $fk_mo, $typmouv, $eatsellby=0)
{
    global $db, $langs;

    //sellby DLC
    //eatby DLUO

    $listlot = '';
    $sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.entity, e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu, ";
    $sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype, m.batch, m.price, m.type_mouvement, pl.rowid as lotid, pl.eatby, pl.sellby, u.login, u.photo, u.lastname, u.firstname";
    $sql .= " FROM " . MAIN_DB_PREFIX . "entrepot as e, ".MAIN_DB_PREFIX."product as p , " . MAIN_DB_PREFIX . "stock_mouvement as m ";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON m.fk_user_author = u.rowid ";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product ";
    $sql .= " WHERE p.rowid = ".$idproduct." AND m.fk_product = ".$idproduct." AND m.origintype = 'mo' AND m.fk_origin = " . $fk_mo . " AND m.fk_entrepot = e.rowid AND e.entity IN (1) AND p.fk_product_type = 0 AND m.type_mouvement = ".$typmouv." AND m.batch iS NOT NULL ORDER BY m.datem";

//    if ($typmouv==3 && $eatsellby == 1) var_dump($sql);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        if ($num>0 && $typmouv!=3) $listlot="\n".$langs->transnoentities('Nolotserie')." : \n";
        $i = 0;
        while ($i < $num && $num) {
            $obj = $db->fetch_object($resql);
            if (!empty($eatsellby)) {
//                var_dump($obj->sellby);
                if (!empty($obj->sellby) || !empty($obj->eatby)) $listlot = $obj->sellby." / ".$obj->eatby;
            }
            else {
                if ($i > 0) $listlot .= "\n";
                $listlot .= $obj->batch;
            }
            $i++;
        }
    }
    $db->free($resql);
    return $listlot;


}


