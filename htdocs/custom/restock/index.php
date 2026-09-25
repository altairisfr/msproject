<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2020-2022 Charlene Benke		<charlene@patas-monkey.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       restock/index.php
 *	\ingroup    restock
 *	\brief      Home page of restock top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


dol_include_once('/restock/class/restock.class.php');


// Load translation files required by the page
$langs->load("restock@restock");

$action=GETPOST('action', 'alpha');

	$restock = New restock($db);

// Securite acces client
//if (! $user->rights->stockmovr->read) accessforbidden();

$socid=GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max=5;
$now=dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$restock = New restock($db);
$entrepotStatic=New Entrepot($db);
$companystatic= new societe($db);

llxHeader("", $langs->trans("RestockArea"));

print load_fiche_titre($langs->trans("RestockArea"), '', 'restock.png@restock');

$pictoOf = img_picto($langs->trans('Factory'), "object_factory@factory");
$pictoOrder = img_picto($langs->trans("SupplierOrder"), "object_order");
$pictoProposal = img_picto($langs->trans("SupplierProposal"), "object_supplier_proposal");

print '<div class="fichecenter">';


print '<div class="fichethirdleft">';
// Wainting stockmovr
$sql = "SELECT rs.*";
$sql.= " FROM ".MAIN_DB_PREFIX."restock as rs";
$sql.= " WHERE rs.status=1";
$sql.= " ORDER BY rs.date_creation";

$resql = $db->query($sql);
if ($resql) {
	$total = 0;
	$num = $db->num_rows($resql);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("RestockWaiting").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'');
	print '</th></tr>';

	$var = true;
	if ($num > 0) {
		$i = 0;

		while ($i < $num) {

			$obj = $db->fetch_object($resql);
			$restock->fetch($obj->rowid);
			print '<tr class="oddeven"><td class="nowrap">';
			print $restock->getNomUrl(1);
			print '</td>';
			// nombre
			print '<td class="right" class="nowrap">';
			if($restock->nbof >0)
				print $pictoOf." ".$restock->nbof." (".$restock->sumQtyOf.")";
			print '</td>';
			print '<td class="right" class="nowrap">';
			if ($restock->nborder >0) 
				print $pictoOrder." ".$restock->nborder." (".$restock->sumQtyOrder.")";
			print '</td>';
			print '<td class="right" class="nowrap">';
			if ($restock->nbproposal >0)
				print $pictoProposal." ".$restock->nbproposal." (".$restock->sumQtyProposal.")";;
			print '</td>';
			print '<td class="right" class="nowrap">'.$restock->getLibStatut(5).'</td>';
			print '</tr>';
			$i++;

		}
		if ($total>0)
			print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="5" class="right">'.price($total)."</td></tr>";
	} else
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoRestock").'</td></tr>';

	print "</table><br>";

	$db->free($resql);
} else
	dol_print_error($db);

print '</div>';

$max=3;

print '<div class="fichetwothirdright"><div class="ficheaddleft">';
// Wainting restock
$sql = "SELECT rs.*";
$sql.= " FROM ".MAIN_DB_PREFIX."restock as rs";
$sql.= " WHERE rs.status=2";
$sql.= " ORDER BY rs.tms";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$total = 0;
	$num = $db->num_rows($resql);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="5">'.$langs->trans("RestockClose").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

	$var = true;
	if ($num > 0) {
		$i = 0;
		while ($i < $num) {

			$obj = $db->fetch_object($resql);
			$restock->fetch($obj->rowid);
			print '<tr class="oddeven"><td class="nowrap">';
			print $restock->getNomUrl(1);
			print '</td>';
			// nombre
			print '<td class="right" class="nowrap">';
			if($restock->nbof >0)
				print $pictoOf." ".$restock->nbof." (".$restock->sumQtyOf.")";
			print '</td>';
			print '<td class="right" class="nowrap">';
			if ($restock->nborder >0) 
				print $pictoOrder." ".$restock->nborder." (".$restock->sumQtyOrder.")";
			print '</td>';
			print '<td class="right" class="nowrap">';
			if ($restock->nbproposal >0)
				print $pictoProposal." ".$restock->nbproposal." (".$restock->sumQtyProposal.")";;
			print '</td>';
			print '<td class="right" class="nowrap">'.$restock->getLibStatut(5).'</td>';
			print '</tr>';
		}
		if ($total>0)
			print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="5" class="right">'.price($total)."</td></tr>";
	} else
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoRestock").'</td></tr>';

	print "</table><br>";

	$db->free($resql);
} else
	dol_print_error($db);

print '</div>';



print '</div></div>';

// End of page
llxFooter();
$db->close();
