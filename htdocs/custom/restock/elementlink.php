<?php
/* Copyright (C) 2012-2020	Charlene BENKE	<charlene@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/restock/elementlink.php
 *	\brief		elementlink restok tabs
 *	\ingroup	restock
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) 
	$res=@include("../../main.inc.php");		// For "custom" directory

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";

require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

//dol_include_once('/equipement/core/modules/equipement/modules_equipement.php');
dol_include_once('/restock/class/restock.class.php');
dol_include_once('/restock/core/lib/restock.lib.php');

$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action		= GETPOST('action', 'alpha');
$id			= GETPOST('id', 'int');

$ref		= GETPOST('ref', 'alpha');
$productid	= GETPOST('productid', 'int');
$action		= GETPOST('action', 'alpha');


$object = new restock($db);
$object->fetch($id, $ref);


// Security check
if ($user->socid)
$socid=$user->socid;
$result = restrictedArea($user, 'restock', $id, 'restock');

/*
 * Actions
 */
$permissiondellink=true;
include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader();

/*
	* Affichage en mode visu
	*/


dol_htmloutput_mesg($mesg);

$head = restockPrepareHead($object);

dol_fiche_head($head, 'link', $langs->trans("Restock"), -1, 'restock@restock');

$linkback = '<a href="list.php?restore_lastsearch_values=1' . (! empty($productid) ? '&productid=' . $productid : '') . '">' . $langs->trans("BackToList") . '</a>';

$morehtmlref='<div class="refidno">';

// Generated Element
if (is_object($object->elementOrigin))
	$morehtmlref.='<br>'.$langs->trans('Element') . ' : ' .$object->elementOrigin->getNomUrl(1);

if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
	if (!empty($object->fk_projet)) {
		$proj = new Project($db);
		$proj->fetch($object->fk_projet);
		$morehtmlref .= $proj->getNomUrl(1);
		$morehtmlref .= " - ".$proj->title;
	} else {
		$morehtmlref .= '';
	}
}

$morehtmlref.='</div>';

dol_banner_tab($object, 'ref', $linkback, 0, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$linktoelem = $form->showLinkToObjectBlock($object);
//$compatibleImportElementsList = array('factory','supplier_order','commande', 'propal'); // import from linked elements
$compatibleImportElementsList = array();
$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

print '</div>';
print "\n";

llxFooter();
$db->close();