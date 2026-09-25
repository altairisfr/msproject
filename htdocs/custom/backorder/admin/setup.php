<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Francis Appels <francis.appels@z-application.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    backorder/admin/setup.php
 * \ingroup backorder
 * \brief   BackOrder setup page.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/backorder.lib.php';
require_once "../class/backorder.class.php";
require_once "../class/backorder_supplier.class.php";

// Translations
$langs->loadLangs(array("admin", "backorder@backorder"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters=array(
	'BACKORDER_CUSTOMER_ORDER'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno', 'action'=>'regenerateCustomerBackorders'),
	'BACKORDER_CUSTOMER_ORDER_NONSHIP'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno'),
	'BACKORDER_SERVICES'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno'),
	'BACKORDER_SUPPLIER_ORDER'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno', 'action'=>'regenerateSupplierBackorders'),
	'BACKORDER_SUPPLIER_ORDER_NONRECEIVE'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno'),
	'BACKORDER_SUPPLIER_SERVICES'=>array('css'=>'minwidth200','enabled'=>1, 'type'=>'yesno')
);


/*
 * Actions
 */

if ($action == 'setYesNo') {
	$db->begin();

	// Process common param fields
	if (is_array($_GET)) {
		foreach ($_GET as $key => $val) {
			if (preg_match('/^param(\w*)$/', $key, $reg)) {
				$param=GETPOST("param".$reg[1], 'alpha');
				$value=GETPOST("value".$reg[1], 'int');
				if ($param) {
					$res = dolibarr_set_const($db, $param, $value, 'yesno', 0, '', $conf->entity);
					if (! $res > 0) $error++;
				}
			}
		}
	}

	if (! $error) {
		$db->commit();
	} else {
		$db->rollback();
		if (empty($nomessageinsetmoduleoptions)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
} elseif ($action == 'update') {
	$db->begin();

	$error=0;
	foreach ($arrayofparameters as $key => $val) {
		if ($arrayofparameters[$key]['type'] == 'text') {
			$result=dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			if ($result < 0) {
				$error++;
				break;
			}
		}
	}

	if (! $error) {
		$db->commit();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
} elseif ($action == 'regenerateCustomerBackorders') {
	$backOrder = new BackOrder($db);

	if ($backOrder->deleteAll() > 0) {
		$result = $backOrder->getBackorder('read');
		if ($result >= 0) {
			setEventMessages($langs->trans("CustomerBackordersGenerated", $result), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("generateBackordersFailed"), null, 'errors');
			setEventMessages($backOrder->error, $backOrder->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans("deleteBackordersFailed"), null, 'errors');
		setEventMessages($backOrder->error, $backOrder->errors, 'errors');
	}
} elseif ($action == 'regenerateSupplierBackorders') {
	$backOrder = new BackOrderSupplier($db);

	if ($backOrder->deleteAll() > 0) {
		$result = $backOrder->getBackorder('read');
		if ($result >= 0) {
			setEventMessages($langs->trans("SupplierBackordersGenerated", $result), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("generateBackordersFailed"), null, 'errors');
			setEventMessages($backOrder->error, $backOrder->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans("deleteBackordersFailed"), null, 'errors');
		setEventMessages($backOrder->error, $backOrder->errors, 'errors');
	}
}

/*
 * View
 */

$page_name = "BackOrderSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_backorder@backorder');

// Configuration header
$head = backorderAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, "backorder@backorder");

// Setup page goes here
echo $langs->trans("BackOrderSetupPage");


if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		if (isset($val['enabled']) && empty($val['enabled'])) continue;

		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		if ($val['type'] == 'yesno') {
			if ($conf->global->$key == "1") {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=0&token='.$_SESSION['newtoken'].'">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</td></tr>';
			} else {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=1&token='.$_SESSION['newtoken'].'">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a></td></tr>';
			}
		} else {
			print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$conf->global->$key.'">';
		}
		print '</td></tr>';
	}

	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td><td></td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		if ($val['type'] == 'yesno') {
			if (!empty($conf->global->$key) && $conf->global->$key == "1") {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=0&token='.$_SESSION['newtoken'].'">';
				print img_picto($langs->trans("Activated"), 'switch_on');
				print '</td>';
				if (!empty($val['action'])) {
					print '<td>';
					print '<div class="tabsAction">';
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=' . $val['action'] . '">'.$langs->trans($val['action']).'</a>';
					print '</div></td>';
				} else {
					print '<td></td>';
				}
			} else {
				print '<td align="left"><a href="'.$_SERVER['PHP_SELF'].'?action=setYesNo&param'.$key.'='.$key.'&value'.$key.'=1&token='.$_SESSION['newtoken'].'">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a></td>';
				print '<td></td>';
			}
		} else {
			print '</td><td>'.$conf->global->$key.'</td>';
		}
		print '</tr>';
	}

	print '</table>';

	//print '<div class="tabsAction">';
	//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	//print '</div>';
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
