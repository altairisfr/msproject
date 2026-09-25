<?php
/* Copyright (C) 2014-2022	Charlene BENKE	 <charlene@patas-monkey.com>
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
 *	  \file	   htdocs/restock/admin/restock.php
 *		\ingroup	restock
 *		\brief	  Page to setup restock module
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) 
	$res=@include("../../../main.inc.php");	// For "custom" directory
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

dol_include_once("/restock/core/lib/restock.lib.php");
dol_include_once("/restock/class/restock.class.php");

// les classes pour les status
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

$langs->load("restock@restock");
$langs->load("admin");
$langs->load("errors");
$langs->load("propal");
$langs->load("orders");
$langs->load("bills");

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');



/*
 * Actions
 */

if ($action == 'setvalue') {
	// save the setting
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_DRAFT", GETPOST('select0propals', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_VALIDATE", GETPOST('select1propals', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_SIGNED", GETPOST('select2propals', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_DRAFT", GETPOST('select0commandes', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_VALIDATE", GETPOST('select1commandes', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_PARTIAL", GETPOST('select2commandes', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_DRAFT", GETPOST('select0factures', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_VALIDATE", GETPOST('select1factures', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_PARTIAL", GETPOST('select2factures', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "RESTOCK_REASSORT_MODE", GETPOST('reassortMode', 'int'), 'chaine', 0, '', $conf->entity);

	// gestion de la transformation après remise de la commande client en commande fournisseur
	dolibarr_set_const(
					$db, "RESTOCK_COEF_ORDER_CLIENT_FOURN", GETPOST('coefcmdclient2fourn', 'int'),
					'chaine', 0, '', $conf->entity
	);
	
	// activation du mode chargement des commandes fournisseurs brouillon
	dolibarr_set_const(
					$db, "RESTOCK_FILL_ORDER_DRAFT", GETPOST('fillOrderDraft', 'int'), 
					'chaine', 0, '', $conf->entity
	);

	// activation du mode chargement des commandes fournisseurs brouillon
	dolibarr_set_const(
					$db, "RESTOCK_FILL_PROPOSAL_DRAFT", GETPOST('fillProposalDraft', 'int'), 
					'chaine', 0, '', $conf->entity
	);

	// activation du mode chargement des commandes fournisseurs brouillon
	dolibarr_set_const(
					$db, "RESTOCK_FILL_FACTORY_DRAFT", GETPOST('fillFactoryDraft', 'int'), 
					'chaine', 0, '', $conf->entity
	);

	// select le type de produit sélectionné
	dolibarr_set_const(
					$db, "RESTOCK_PRODUCT_TYPE_SELECT", GETPOST('producttypeselect', 'int'), 
					'chaine', 0, '', $conf->entity
	);

	// Gestion de la cr�ation automatique des commandes clients
	dolibarr_set_const($db, "RESTOCK_DBNAME_FOURNISH", GETPOST('dbnamefournish'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const(
					$db, "RESTOCK_PREFIXTABLE_FOURNISH", GETPOST('prefixtablefournish'), 
					'chaine', 0, '', $conf->entity
	);
	dolibarr_set_const(
					$db, "RESTOCK_CUSTOMID_FOURNISH", GETPOST('customidfournish'), 
					'chaine', 0, '', $conf->entity
	);
	dolibarr_set_const($db, "RESTOCK_RECURSIVITY_DEEP", GETPOST('recursivitedeep'), 'chaine', 0, '', $conf->entity);

	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

// Get setting 
$select0propals=		!empty($conf->global->RESTOCK_PROPOSAL_DRAFT)?$conf->global->RESTOCK_PROPOSAL_DRAFT:"";
$select1propals=		!empty($conf->global->RESTOCK_PROPOSAL_VALIDATE)?$conf->global->RESTOCK_PROPOSAL_VALIDATE:"";
$select2propals=		!empty($conf->global->RESTOCK_PROPOSAL_SIGNED)?$conf->global->RESTOCK_PROPOSAL_SIGNED:"";
$select0commandes=		!empty($conf->global->RESTOCK_ORDER_DRAFT)?$conf->global->RESTOCK_ORDER_DRAFT:"";
$select1commandes=		!empty($conf->global->RESTOCK_ORDER_VALIDATE)?$conf->global->RESTOCK_ORDER_VALIDATE:"";
$select2commandes=		!empty($conf->global->RESTOCK_ORDER_PARTIAL)?$conf->global->RESTOCK_ORDER_PARTIAL:"";
$select0factures=		!empty($conf->global->RESTOCK_BILL_DRAFT)?$conf->global->RESTOCK_BILL_DRAFT:"";
$select1factures=		!empty($conf->global->RESTOCK_BILL_VALIDATE)?$conf->global->RESTOCK_BILL_VALIDATE:"";
$select2factures=		!empty($conf->global->RESTOCK_BILL_PARTIAL)?$conf->global->RESTOCK_BILL_PARTIAL:"";
$coefcmdclient2fourn=	!empty($conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN)?$conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN:"";
$reassortMode=			!empty($conf->global->RESTOCK_REASSORT_MODE)?$conf->global->RESTOCK_REASSORT_MODE:"";
$dbnamefournish=		!empty($conf->global->RESTOCK_DBNAME_FOURNISH)?$conf->global->RESTOCK_DBNAME_FOURNISH:"";
$prefixtablefournish=	!empty($conf->global->RESTOCK_PREFIXTABLE_FOURNISH)?$conf->global->RESTOCK_PREFIXTABLE_FOURNISH:"";
$customidfournish=		!empty($conf->global->RESTOCK_CUSTOMID_FOURNISH)?$conf->global->RESTOCK_CUSTOMID_FOURNISH:"";

$fillProposalDraft=		!empty($conf->global->RESTOCK_FILL_PROPOSAL_DRAFT)?$conf->global->RESTOCK_FILL_PROPOSAL_DRAFT:"";
$fillOrderDraft=		!empty($conf->global->RESTOCK_FILL_ORDER_DRAFT)?$conf->global->RESTOCK_FILL_ORDER_DRAFT:"";
$fillFactoryDraft=		!empty($conf->global->RESTOCK_FILL_FACTORY_DRAFT)?$conf->global->RESTOCK_FILL_FACTORY_DRAFT:"";


$recursivitedeep=		!empty($conf->global->RESTOCK_RECURSIVITY_DEEP)?$conf->global->RESTOCK_RECURSIVITY_DEEP:"";
$producttypeselect=		!empty($conf->global->RESTOCK_PRODUCT_TYPE_SELECT)?$conf->global->RESTOCK_PRODUCT_TYPE_SELECT:"";


if ($action == 'SubstractQtyFromRunningOrder')
	dolibarr_set_const($db, "RESTOCK_ORDER_RUNNING_SUBSTRACT", $value, 'chaine', 0, '', $conf->entity);
$SubstractQtyFromRunningOrder = !empty($conf->global->RESTOCK_ORDER_RUNNING_SUBSTRACT)?$conf->global->RESTOCK_ORDER_RUNNING_SUBSTRACT:"";

if ($action == 'AddLinkOrderByProject')
	dolibarr_set_const($db, "RESTOCK_ADD_LINKORDERBYPROJECT", $value, 'chaine', 0, '', $conf->entity);
$addLinkOrderByProject = !empty($conf->global->RESTOCK_ADD_LINKORDERBYPROJECT)?$conf->global->RESTOCK_ADD_LINKORDERBYPROJECT:"";

if ($action == 'RecursivOrderOnFactoryOF')
	dolibarr_set_const($db, "RESTOCK_FACTORY_RECURSIVE_OF", $value, 'chaine', 0, '', $conf->entity);
$RecursivOrderOnFactoryOF= !empty($conf->global->RESTOCK_FACTORY_RECURSIVE_OF)?$conf->global->RESTOCK_FACTORY_RECURSIVE_OF:"";


if ($action == 'CustomerOrderRefInPrivateNote')
	dolibarr_set_const($db, "RESTOCK_ADD_CUSTORDERREF_IN_PRIVATENOTE", $value, 'chaine', 0, '', $conf->entity);
$customerOrderRefInPrivateNote = !empty($conf->global->RESTOCK_ADD_CUSTORDERREF_IN_PRIVATENOTE)?$conf->global->RESTOCK_ADD_CUSTORDERREF_IN_PRIVATENOTE:"";



/*
 * View
 */

$page_name = $langs->trans("RestockSetup") . " - " .$langs->trans("MoreSetup");
$tab = $langs->trans("reStock");


llxHeader("", $page_name, 'EN:Restock_Configuration|FR:Configuration_module_Restock|ES:Configuracion_Restock');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($page_name, $linkback, 'title_setup');

$head = restock_admin_prepare_head();
dol_fiche_head($head, 'restock', $tab, -1, 'restock@restock');


// la sélection des status à suivre dans le process commercial

print '<form method="post" action="restock.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans("BusinessDocuments").'</td>';
print '<td colspan=6  align=center>'.$langs->trans("DisplayPonderedSetting").'</td>';
print '</tr >';

// ligne des propales
$generic_status = new Propal($db);
print '<tr >';
print '<td>'.$langs->trans("Proposal").'</td>';
print checkvalue('select0propals', $select0propals, $generic_status->LibStatut(0, 1));
print checkvalue('select1propals', $select1propals, $generic_status->LibStatut(1, 1));
print checkvalue('select2propals', $select2propals, $generic_status->LibStatut(2, 1));
print '</tr>'."\n";

// liste des commandes
$generic_status = new Commande($db);
print '<tr >';
print '<td>'.$langs->trans("Order").'</td>';
print checkvalue('select0commandes', $select0commandes, $generic_status->LibStatut(0, 0, 1));
print checkvalue('select1commandes', $select1commandes, $generic_status->LibStatut(1, 0, 1));
print checkvalue('select2commandes', $select2commandes, $generic_status->LibStatut(2, 0, 1));

print '</tr>'."\n";
// ligne des factures
$generic_status = new Facture($db);
print '<tr >';
print '<td>'.$langs->trans("Bill").'</td>';
print checkvalue('select0factures', $select0factures, $generic_status->LibStatut(0, 0, 1, -1));
print checkvalue('select1factures', $select1factures, $generic_status->LibStatut(0, 1, 1, -1));
print checkvalue(
				'select2factures', $select2factures, 
				$generic_status->LibStatut(0, 3, 1, 1)
);
print '</tr>'."\n";
// seulement si il y a à la fois les produit et les services
if (! empty($conf->product->enabled) && ! empty($conf->service->enabled)) {
	print '<tr ><td valign=top>'.$langs->trans("TypeOfProduct").'</td>';
	print '<td colspan=5 valign=top>'.$langs->trans("InfoTypeOfProduct").'</td>';
	print '<td  align=right valign=top><select name="producttypeselect">';
		print '<option value="0" >'.$langs->trans("ProductAndService").'</option>';
		print '<option value="1" '.($producttypeselect==1?' selected ':'').'>'.$langs->trans("OnlyProduct").'</option>';
		print '<option value="2" '.($producttypeselect==2?' selected ':'').'>'.$langs->trans("OnlyService").'</option>';
	print '</select></td>';
	print '</tr>'."\n";
}

print '<tr ><td width=20% valign=top>'.$langs->trans("ReassortMode").'</td>';
print '<td colspan=5 valign=top>'.$langs->trans("InfoReassortMode").'</td>';
print '<td  align=right valign=top><select name="reassortMode">';
	print '<option value="0" >'.$langs->trans("All").'</option>';
	print '<option value="1" '.($reassortMode==1?' selected ':'').'>';
	print $langs->trans("ReassortModeWithoutStock").'</option>';
	print '<option value="2" '.($reassortMode==2?' selected ':'').'>';
	print $langs->trans("ReassortModeWithoutOrder").'</option>';
	print '<option value="3" '.($reassortMode==3?' selected ':'').'>';
	print $langs->trans("ReassortModeWithoutAll").'</option>';
print '</select></td>';
print '</tr>'."\n";
print '</table><br>';

print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td colspan=3>'.$langs->trans("Other").'</td>';
print '</tr >';
// prise en compte des quantités expédiées
print '<tr>';
print '<td>'.$langs->trans("SubstractQtyFromRunningOrder").'</td>';
print '<td>'.$langs->trans("InfoSubstractQtyFromRunningOrder").'</td>';
print '<td align=center>';
if ($SubstractQtyFromRunningOrder == 1) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=SubstractQtyFromRunningOrder&value=0">';
	print img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=SubstractQtyFromRunningOrder&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';


// si on a les deux type de produits possibles
print '<tr ><td width=20% valign=top>'.$langs->trans("FillDraftProposal").'</td>';
print '<td  valign=top>'.$langs->trans("InfoFillDraftProposal").'</td>';
print '<td  align=right valign=top><select name="fillProposalDraft">';
print '<option value="0" >'.$langs->trans("Disabled").'</option>';
print '<option value="1" '.($fillProposalDraft==1?' selected ':'').'>'.$langs->trans("FillDraft").'</option>';
print '<option value="3" '.($fillProposalDraft==3?' selected ':'').'>'.$langs->trans("FillDraftGather").'</option>';
print '<option value="2" '.($fillProposalDraft==2?' selected ':'').'>'.$langs->trans("FillDraftByUser").'</option>';
print '<option value="4" '.($fillProposalDraft==4?' selected ':'').'>'.$langs->trans("FillDraftByUserGather").'</option>';
print '</select></td>';
print '</tr>';

print '<tr ><td valign=top>'.$langs->trans("FillDraftOrder").'</td>';
print '<td  valign=top>'.$langs->trans("InfoFillDraftOrder").'</td>';
print '<td  align=right valign=top><select name="fillOrderDraft">';
print '<option value="0" >'.$langs->trans("Disabled").'</option>';
print '<option value="1" '.($fillOrderDraft==1?' selected ':'').'>'.$langs->trans("FillDraft").'</option>';
print '<option value="3" '.($fillOrderDraft==3?' selected ':'').'>'.$langs->trans("FillDraftGather").'</option>';
print '<option value="2" '.($fillOrderDraft==2?' selected ':'').'>'.$langs->trans("FillDraftByUser").'</option>';
print '<option value="4" '.($fillOrderDraft==4?' selected ':'').'>'.$langs->trans("FillDraftByUserGather").'</option>';
print '</select></td>';
print '</tr>';

if (!empty($conf->factory->enabled)) {
	print '<tr ><td valign=top>'.$langs->trans("FillDraftFactory").'</td>';
	print '<td  valign=top>'.$langs->trans("InfoFillDraftFactory").'</td>';
	print '<td  align=right valign=top><select name="fillFactoryDraft">';
	print '<option value="0" >'.$langs->trans("Disabled").'</option>';
	print '<option value="1" '.($fillFactoryDraft==1?' selected ':'').'>'.$langs->trans("FillDraft").'</option>';
	print '<option value="3" '.($fillFactoryDraft==3?' selected ':'').'>'.$langs->trans("FillDraftGather").'</option>';
	print '<option value="2" '.($fillFactoryDraft==2?' selected ':'').'>'.$langs->trans("FillDraftByUser").'</option>';
	print '<option value="4" '.($fillFactoryDraft==4?' selected ':'').'>'.$langs->trans("FillDraftByUserGather").'</option>';
	print '</select></td>';
	print '</tr>';
}

print '</table><br>'."\n";

print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td colspan=3>'.$langs->trans("OtherRestockMode").'</td>';
print '</tr >';

print '<tr ><td width=20% valign=top>'.$langs->trans("CoefCommandeClient2fournisseur").'</td>';
print '<td valign=top>'.$langs->trans("InfoCoefCommandeClient2fournisseur").'</td>';
print '<td width=10% valign=top align=right>';
print checkvalue('coefcmdclient2fourn', $coefcmdclient2fourn, $langs->trans("Remise"), 0);
print '</td>';
print '</tr>'."\n";

print '<tr>';
print '<td>'.$langs->trans("AddLinkOrderByProject").'</td>';
print '<td>'.$langs->trans("InfoAddLinkOrderByProject").'</td>';
print '<td>';
if ($addLinkOrderByProject == 1) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=AddLinkOrderByProject';
	print '&value=0">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=AddLinkOrderByProject';
	print '&value=1">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';
print '<tr>';
print '<td>'.$langs->trans("CustomerOrderRefInPrivateNote").'</td>';
print '<td>'.$langs->trans("InfoCustomerOrderRefInPrivateNote").'</td>';
print '<td>';
if ($customerOrderRefInPrivateNote == 1) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=CustomerOrderRefInPrivateNote&value=0">';
	print img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=CustomerOrderRefInPrivateNote&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';
if (!empty($conf->factory->enabled)) {
	print '<tr>';
	print '<td>'.$langs->trans("RecursivOrderOnFactoryOF").'</td>';
	print '<td>'.$langs->trans("InfoRecursivOrderOnFactoryOF").'</td>';
	print '<td>';
	if ($RecursivOrderOnFactoryOF == 1) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=RecursivOrderOnFactoryOF&value=0">';
		print img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
	} else {
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=RecursivOrderOnFactoryOF&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	}
	print '</td></tr>';
}


//print '<tr ><td colspan=3><hr></td></tr>';
//print '<tr ><td valign=top>'.$langs->trans("CreateCustomerOrderfromOtherDB").'</td>';
//print '<td  valign=top>'.$langs->trans("InfoCreateCustomerOrderfromOtherDB").'</td>';
//
//print '<td  align=right valign=top>';
//print $langs->trans("DatabaseNameFourn")." <input type=text name=dbnamefournish value='".$dbnamefournish."'><br>";
//print $langs->trans("PrefixTableFourn");
//print ""<input type=text name=prefixtablefournish size=5 value='".$prefixtablefournish."'><br>";
//print $langs->trans("CustomIdFourn")." <input type=text name=customidfournish size=3 value='".$customidfournish."'>";
//print '</td>';
//print '</tr>'."\n";
print '</table>';



// Boutons d'action
print '<div class="tabsAction">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';
print '</td></tr>'."\n";

print '</form>';

// Footer
llxFooter();
$db->close();

function checkvalue($selectname, $selectValue, $label, $withTD=1)
{
	global $db;
	$formother = new FormOther($db);
	$temp= "";
	if ($withTD)
		$temp= '<td align=left width=5% valign=top>';
	$temp.=" ".$formother->select_percent($selectValue, $selectname);
	if ($withTD)
		$temp.='</td><td align=left>'.$label.'</td>';
	else
		$temp.="&nbsp;".$label;

	return $temp;
}