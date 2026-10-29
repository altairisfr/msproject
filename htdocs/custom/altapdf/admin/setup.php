<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Christophe Battarel	<christophe.battarel@altairis.fr>
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
 * \file    altapdf/admin/setup.php
 * \ingroup altapdf
 * \brief   altapdf setup page.
 */

// Load Dolibarr environment
require_once "../main_module.inc.php";
/**
 * @var DoliDB $db
 * @var Translate $langs
 */

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
//require_once '../lib/altapdf.lib.php';
//require_once "../class/myclass.class.php";

require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
$form = new Form($db);

require_once DOL_DOCUMENT_ROOT . "/product/class/html.formproduct.class.php";
$formproduct = new FormProduct($db);

// Translations
$langs->loadLangs(array("admin", "shipping@altapdf", "altapdf@altapdf"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$var = GETPOST('var', 'alpha');
$value = GETPOST($var, 'alpha');

$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters=array(
	'GeneralSetupPage' => array('type' => 'titre'),
	'ALTAPDF_PREFIX_PDF' => array('type' => 'select_prefix_pdf'), // par défaut activé à 2
	'ALTAPDF_MANUAL_PREFIX_PDF' => array('type' => 'default'),
	'ALTAPDF_CUSTOMCODE_COUNTRY' => array('type' => 'bool'), // Affiche la monenclacture (code SH) et le pays d'origne)
	'PDF_HIDE_SALES_REP_IN_SENDER_FRAME' => array('type' => 'bool'), // Affiche le contact responsable suivi commande
	'ALTAPDF_EXONERATION_TVA_LEGAL_NOTICE' => array('type' => 'bool'), // Affiche les mentions légales - Exonération TVA
	'ALTAPDF_ADD_TERMSOFSALE_PROPAL' => array('type' => 'bool'), // Affiche les CGV dans le pdf, prend en compte MAIN_PDF_ADD_TERMSOFSALE_PROPAL
	'ALTAPDF_ADD_TERMSOFSALE_ORDER' => array('type' => 'bool'), // Affiche les CGV dans le pdf, prend en compte MAIN_PDF_ADD_TERMSOFSALE_ORDER
	'ALTAPDF_ADD_TERMSOFSALE_INVOICE' => array('type' => 'bool'), // Affiche les CGV dans le pdf, prend en compte MAIN_PDF_ADD_TERMSOFSALE_INVOICE
	'ALTAPDF_HIDE_COMPANY_NAME_IN_SHIPPING_ADDRESS' => array('type' => 'bool'), // Cache le nom de la société dans le bloc adresse de livraison si le contact de livraison est défini
	'ALTAPDF_HIDE_PROFID1_PROFID2_SHIPPING_ADDRESS' => array('type' => 'bool'), // Cacher le siret et le siren dans le bloc adresse de livraison (devis, commande client, facture)
	'ShippingSetupPage' => array('type' => 'titre'),
	'ALTAPDF_SHIPPING_CARRIER_IN_INFOS' => array('type' => 'bool'),
	'ALTAPDF_SHIPPING_DELIVERY_ADDRESS_IN_INFOS' => array('type' => 'bool'),
	'ALTAPDF_SHIPPING_CUSTOMER_CONTACT_IN_INFOS' => array('type' => 'bool'),
	'ALTAPDF_FORCE_HIDEPACK_DETAILS' => array('type' => 'bool'),
	'ALTAPDF_PRINT_UNSHIPPED_LINES' => array('type' => 'bool'),
	'SupplierOrderShippingSetupPage' => array('type' => 'titre'),
	'ALTAPDF_ADD_WAREHOUSE' => array('type' => 'multiselect_warehouse'), // Choix des entrepôts ouverts (choix multiple)
	'ALTAPDF_ADD_TIER' => array('type' => 'multiselect_tier'), // Choix des clients / fournisseurs (choix multiple)
	// 'PropalSetupPage' => array('type' => 'titre'),
	// 'ALTAPDF_PROPOSAL_USE_ELECTRONIC_SIGNING' => array('type' => 'bool'), n'est plus utile
);
$defaultvalue = $arrayofparameters[$var]['default'] ?? '';

/*
 * Actions
 */

if ($action == 'activate') {
	dolibarr_set_const($db, $var, "1", 'chaine', 0, '', $conf->entity);
} elseif ($action == 'disable') {
	dolibarr_del_const($db, $var, $conf->entity);
} elseif ($action == 'set') {
	dolibarr_set_const($db, $var, $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'reset') {
	dolibarr_set_const($db, $var, $defaultvalue, 'chaine', 0, '', $conf->entity);
}

if ($action == 'prefix_pdf') {
	if (GETPOSTINT('activate_ALTAPDF_PREFIX_PDF')) {
		// Si choix du select préfix non vide on l'ajoute
		$prefix = GETPOSTINT('activate_ALTAPDF_PREFIX_PDF');
		$res = dolibarr_set_const($db, "ALTAPDF_PREFIX_PDF", $prefix, 'int', 0, '', $conf->entity);
		if (getDolGlobalString('ALTAPDF_MANUAL_PREFIX_PDF') != '') {
			// On vide le préfix manuel si non vide
			$res = dolibarr_set_const($db, "ALTAPDF_MANUAL_PREFIX_PDF", '', 'chaine', 0, '', $conf->entity);
		}

	} elseif (GETPOSTINT('activate_ALTAPDF_PREFIX_PDF') == 0 && getDolGlobalString('ALTAPDF_MANUAL_PREFIX_PDF') == '') {
		// Si choix du select préfix vide et que le préfix manuel est vide
		// On met par défaut le nom de la société
		$prefix = GETPOSTINT('activate_ALTAPDF_PREFIX_PDF');
		$prefix_auto = $_SESSION["dol_company"];
		$res = dolibarr_set_const($db, "ALTAPDF_PREFIX_PDF", $prefix, 'int', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "ALTAPDF_MANUAL_PREFIX_PDF", $prefix_auto, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set' && $var == 'ALTAPDF_MANUAL_PREFIX_PDF' && $value != '') {
	// Si choix du préfix manuel
	// on vide le select préfix
	$res = dolibarr_set_const($db, "ALTAPDF_PREFIX_PDF", 0, 'int', 0, '', $conf->entity);
} elseif ($action == 'set' && $var == 'ALTAPDF_MANUAL_PREFIX_PDF' && $value == '') {
	// Si choix de vider le préfix manuel
	// On met par défaut le nom de la société dans le select préfix
	if (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 0) {
		$res = dolibarr_set_const($db, "ALTAPDF_PREFIX_PDF", 2, 'int', 0, '', $conf->entity);
	}
}

if ($action == "warehouse_default") {
	if (!empty(GETPOST('warehouse'))) {
		$warehouse_string = implode(';', GETPOST('warehouse'));
	}
	dolibarr_set_const($db, 'ALTAPDF_ADD_WAREHOUSE', $warehouse_string, 'string', 0, '', $conf->entity);

	// On crée, supprime et met à jour l'etrafield shipping_site_supplier_order mutalisant les données des constantes ALTAPDF_ADD_WAREHOUSE et ALTAPDF_ADD_TIER pour créer une liste unique

	// On vérifie que l'on n'a pas déjà des données pour les clients / fournisseurs
	$tier = getDolGlobalString('ALTAPDF_ADD_TIER');
	if (!empty($tier)) {
		$tier = explode(';', $tier);
		$i = 0;
		foreach ($tier as $id_tier) {
			require_once DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";
			$name_tier = new Societe($db);
			$name_tier->fetch($id_tier);
			$array_tier[$i] = $name_tier->name;
			$i ++;
		}
		$shipping['options'] = $array_tier;
	}

	// Création tableau avec id et label des entrepôts
	$warehouse = getDolGlobalString('ALTAPDF_ADD_WAREHOUSE');
	if (!empty($warehouse)) {
		$warehouse = explode(';', $warehouse);
		$array_warehouse = array();
		$i = 0;
		foreach ($warehouse as $id_warehouse) {
			require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
			$name_warehouse = new Entrepot($db);
			$name_warehouse->fetch($id_warehouse);
			$array_warehouse[$i] = $name_warehouse->label;
			$i ++;
		}
	}

	if (!empty($shipping['options']) && !empty($warehouse)) {
		$shipping['options'] = array_merge($shipping['options'], $array_warehouse);
		// $shipping['options'] = $shipping['options'] + $array_warehouse;
	} elseif (empty($shipping['options']) && !empty($warehouse)) {
		$shipping['options'] = $array_warehouse;
	} else {
		$shipping['options'] = $array_tier;
	}

	// Réassigné les valeurs des tableau pour avoir la clé 0 à vide
	$last_value = $shipping['options'][count($shipping['options']) - 1]; // On garde la dernière valeur
	// On décale chaque valeur à partir de la clé 1
	for ($i = count($shipping['options']) - 1; $i > 0; $i--) {
		$shipping['options'][$i] = $shipping['options'][$i - 1];
	}
	$shipping['options'][0] = ''; // On met la clé 0 à vide
	$shipping['options'][] = $last_value; // Ajout de la dernière valeur au tableau

	require_once DOL_DOCUMENT_ROOT . "/core/class/extrafields.class.php";
	$supplier_order = new ExtraFields($db);
	$supplier_order->fetch_name_optionals_label('commande_fournisseur');

	// Si l'extrafield existe pas on le créer avec les valeurs de $shipping['options']
	if (!array_key_exists("shipping_site_supplier_order", $supplier_order->attributes["commande_fournisseur"]["label"])) {
		$supplier_order->addExtraField('shipping_site_supplier_order', 'ShippingSiteSupplierOrder', 'select', 9, '', 'commande_fournisseur', 0, 0, '', $shipping, 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
	} elseif (!getDolGlobalString('ALTAPDF_ADD_WAREHOUSE') && !getDolGlobalString('ALTAPDF_ADD_TIER')) {
		$supplier_order->delete('shipping_site_supplier_order', 'commande_fournisseur');
	} else {
		// Si l'extrafield existe on met à jours avec les nouvelles valeurs de $shipping['options']
		$supplier_order->updateExtraField('shipping_site_supplier_order', 'ShippingSiteSupplierOrder', 'select', 9, '', 'commande_fournisseur', 0, 0, '', $shipping, 1, '', 1, '', '', $conf->entity, 'altapdf@altapdf');
	}
}
if ($action == "tier_default") {
	if (!empty(GETPOST('tier'))) {
		$tier_string = implode(';', GETPOST('tier'));
	}
	dolibarr_set_const($db, 'ALTAPDF_ADD_TIER', $tier_string, 'string', 0, '', $conf->entity);

	// On crée, supprime et met à jour l'etrafield shipping_site_supplier_order mutalisant les données des constantes ALTAPDF_ADD_TIER et ALTAPDF_ADD_WAREHOUSE pour créer une liste unique

	// On vérifie que l'on n'a pas déjà des données pour les entrepôts
	$warehouse = getDolGlobalString('ALTAPDF_ADD_WAREHOUSE');
	if (!empty($warehouse)) {
		$warehouse = explode(';', $warehouse);
		$i = 0;
		foreach ($warehouse as $id_warehouse) {
			require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
			$name_warehouse = new Entrepot($db);
			$name_warehouse->fetch($id_warehouse);
			$array_warehouse[$i] = $name_warehouse->label;
			$i ++;
		}
		$shipping['options'] = $array_warehouse;
	}

	// Création tableau avec id et label des tiers
	$tier = getDolGlobalString('ALTAPDF_ADD_TIER');
	if (!empty($tier)) {
		$tier = explode(';', $tier);
		$array_tier = array();
		$i = 0;
		foreach ($tier as $id_tier) {
			require_once DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";
			$name_tier = new Societe($db);
			$name_tier->fetch($id_tier);
			$array_tier[$i] = $name_tier->name;
			$i ++;
		}
	}
	if (!empty($shipping['options']) && !empty($array_tier)) {
		$shipping['options'] = array_merge($shipping['options'], $array_tier);
		// $shipping['options'] = $shipping['options'] + $array_tier;
	} elseif (empty($shipping['options']) && !empty($array_tier)) {
		$shipping['options'] = $array_tier;
	} else {
		$shipping['options'] = $array_warehouse;
	}

	// Réassigné les valeurs des tableau pour avoir la clé 0 à vide
	$last_value = $shipping['options'][count($shipping['options']) - 1]; // On garde la dernière valeur
	// On décale chaque valeur à partir de la clé 1
	for ($i = count($shipping['options']) - 1; $i > 0; $i--) {
		$shipping['options'][$i] = $shipping['options'][$i - 1];
	}
	$shipping['options'][0] = ''; // On met la clé 0 à vide
	$shipping['options'][] = $last_value; // Ajout de la dernière valeur au tableau

	require_once DOL_DOCUMENT_ROOT . "/core/class/extrafields.class.php";
	$supplier_order = new ExtraFields($db);
	$supplier_order->fetch_name_optionals_label('commande_fournisseur');

	// Si l'extrafield existe pas on le créer avec les valeurs de $shipping['options']
	if (!array_key_exists("shipping_site_supplier_order", $supplier_order->attributes["commande_fournisseur"]["label"])) {
		$supplier_order->addExtraField('shipping_site_supplier_order', 'ShippingSiteSupplierOrder', 'select', 9, '', 'commande_fournisseur', 0, 0, '', $shipping, 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
	} elseif (!getDolGlobalString('ALTAPDF_ADD_TIER') && !getDolGlobalString('ALTAPDF_ADD_WAREHOUSE') ) {
		$supplier_order->delete('shipping_site_supplier_order', 'commande_fournisseur');
	} else {
		// Si l'extrafield existe on met à jours avec les nouvelles valeurs de $shipping['options']
		$supplier_order->updateExtraField('shipping_site_supplier_order', 'ShippingSiteSupplierOrder', 'select', 9, '', 'commande_fournisseur', 0, 0, '', $shipping, 1, '', 1, '', '', $conf->entity, 'altapdf@altapdf');
	}
}

/*
 * View
 */

$page_name = "AltapdfSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_altapdf@altapdf');

// Configuration header
//$head = altapdfAdminPrepareHead();

// affichage onglet
// print dol_get_fiche_head($head, 'shipping', '', -1, "altapdf@altapdf");

// Setup page goes here

if (! empty($arrayofparameters)) {
	foreach($arrayofparameters as $key => $desc) {
		switch ($desc['type']) {
			case 'titre' :
				print load_fiche_titre($langs->trans($key), '', '');
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td width="80%">'.$langs->trans("Parameter").'</td><td class="center" width="20%">'.$langs->trans("Value").'</td></tr>';
				break;
			case 'bool' :
				print '<tr class="oddeven">';
				print '<td width="80%">';
				print $langs->trans($key);
				print '</td>';
				print '<td class="center" width="20%">';
				if (empty($conf->global->$key)) {
					print '<a href="'.$_SERVER['PHP_SELF'].'?action=activate&var='.$key.'&token='.newToken().'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
				} else {
					print '<a href="'.$_SERVER['PHP_SELF'].'?action=disable&var='.$key.'&token='.newToken().'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
				}
				$desc = $langs->trans($key.'Desc', $desc['desclink'] ?? '');
				if ($desc != $key.'Desc') print '&emsp;'.$desc;
				print '</td>';
				break;
			case 'select_prefix_pdf' :
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="prefix_pdf">';
				print '<tr class="oddeven">';
				print '<td>'. $form->textwithpicto($langs->trans("PrefixPDF"), $langs->trans($key.'Tooltip')).'</td>';
				print '<td width="30" class="center">';
				$arrval = array(
					'0'=>$langs->trans(""),
					'1'=>$langs->trans("NoPrefix"),
					'2'=>$langs->trans("NameSociete"),
					'3'=>$langs->trans("NamePdfModele"),
				);
				print $form->selectarray("activate_ALTAPDF_PREFIX_PDF", $arrval, getDolGlobalString('ALTAPDF_PREFIX_PDF'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp');
				print '<input type="submit" class="button small reposition" name="ALTAPDF_PREFIX_PDF" value="'.$langs->trans("Modify").'">';
				print '</td>';
				// print '<td>'.$langs->trans('test').'</td>';
				print '</tr>';
				print '</form>';
				break;
			case 'multiselect_warehouse' :
				print '<tr class="oddeven">';
				print '<td width="80%">';
				print $langs->trans($key);
				print '</td>';
				print '<td class="center" width="20%">';
				$warehouses = getDolGlobalString('ALTAPDF_ADD_WAREHOUSE');
				$selected_warehouses = array();
				if (!empty($warehouses)) {
					$selected_warehouses = explode(';', $warehouses);
				}
				print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
				print '<input type="hidden" name="action" value="warehouse_default" />';
				print '<input type="hidden" name="token" value="' . newToken() . '">';
				print img_picto('', 'stock');
				$warehousestatus = 'warehouseopen'; // ,warehouseinternal
				$formproduct->loadWarehouses(0, '', $warehousestatus);
				$entrepots = array();
				foreach($formproduct->cache_warehouses as $id => $arraytypes) {
					$entrepots[$id] = $arraytypes['label'];
				}
				print $form->multiselectarray('warehouse', $entrepots, $selected_warehouses);
				print '<input type="submit" class="button small reposition" value="'.$langs->trans('Modify').'" />';
				print '</form>';
				$desc = $langs->trans($key.'Desc', $desc['desclink'] ?? '');
				if ($desc != $key.'Desc') print '&emsp;'.$desc;
				print '</td>';
				break;
			case 'multiselect_tier' :
				print '<tr class="oddeven">';
				print '<td width="80%">';
				print $langs->trans($key);
				print '</td>';
				print '<td class="center" width="20%">';
				$tiers = getDolGlobalString('ALTAPDF_ADD_TIER');
				$selected_tiers = array();
				if (!empty($tiers)) {
					$selected_tiers = explode(';', $tiers);
				}
				print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
				print '<input type="hidden" name="action" value="tier_default" />';
				print '<input type="hidden" name="token" value="' . newToken() . '">';
				// print img_picto('', '');
				$TRes = $form->select_thirdparty_list('', '', 's.status > 0', 1, 1, 1, array(), '', 1, 0, '', '', false);
				$t_tiers = array();
				foreach ($TRes as $tier) {
					$t_tiers[$tier['key']] = $tier['label'];
				}
				print $form->multiselectarray('tier', $t_tiers, $selected_tiers, 0, 0, 'minwidth500');
				print '<input type="submit" class="button small reposition" value="'.$langs->trans('Modify').'" />';
				print '</form>';
				$desc = $langs->trans($key.'Desc', $desc['desclink'] ?? '');
				if ($desc != $key.'Desc') print '&emsp;'.$desc;
				print '</td>';
				break;
			default :
				print '<tr class="oddeven"><td>';
				print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
				print '</td>';

				print '<td  class="center">';
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display: inline;">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="set" />';
				print '<input type="hidden" name="var" value="'.$key.'" />';
				print '<input type="text" name="'.$key.'" value="'.getDolGlobalString($key).'" />';
				print '&emsp;';
				print '<input type="submit" class="button small reposition" value="'.$langs->trans("Update").'" />';
				print '</form>';
				// print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display: inline;">';
				// print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				// print '<input type="hidden" name="action" value="reset" />';
				// print '<input type="hidden" name="var" value="'.$key.'" />';
				// print '&emsp;';
				// print '<input type="submit" class="button" value="'.$langs->trans("SetDefault").'" />';
				// print '</form>';
				print '</td>';
		}

		print '</tr>';
	}
	print '</table>';

	print '<br>';
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
