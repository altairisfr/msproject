<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2022	Charlene BENKE	<charlene@patas-monkey.com>
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
 *  \file       note.php
 *  \ingroup    restock
 *  \brief      Car with notes on restock
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

dol_include_once('/restock/class/restock.class.php');
dol_include_once('/restock/core/lib/restock.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("restock@restock", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new restock($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->restock->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('restocknote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'stockmovr', $id);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

$permissionnote = $user->rights->restock->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->restock->write; // Used by the include of actions_addupdatedelete.inc.php



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 * View
 */

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
llxHeader('', $langs->trans('restock'), $help_url);

if ($id > 0 || !empty($ref))
{
	$object->fetch_thirdparty();

	$head = restockPrepareHead($object);

	dol_fiche_head($head, 'notes', $langs->trans("restock"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/restock/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
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

	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
