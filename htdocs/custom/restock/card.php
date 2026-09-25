<?php
/* Copyright (C) 2017		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2020-2022	Charlene BENKE		<charlene@patas-monkey.com>
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
 *   	\file       card.php
 *		\ingroup    restock
 *		\brief      Page to create/edit/view restock
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

//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

dol_include_once('/restock/class/restock.class.php');
dol_include_once('/restock/core/lib/restock.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("restock@restock", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action 	= GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'restockcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new restock($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->restock->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('restockcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'restock', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissiontoread = $user->rights->restock->lire;
$permissiontoadd = $user->rights->restock->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->restock->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->restock->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->restock->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->restock->multidir_output[isset($object->entity) ? $object->entity : 1];


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/restock/list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
    	if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
    		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
    		else $backtopage = dol_buildpath('/restock/card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
    	}
    }
    $triggermodname = 'RESTOCK_MODIFY'; // Name of trigger action code to execute when we modify record

    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	if( $action== "update") {
		// on ajoute la mise à jours des lignes de progressions
		if (!empty($object->table_element_line)) {
			// Show object lines
			$result = $object->getLinesArray();
			if (!empty($object->lines)) {
				foreach ($object->lines as $line) {
					//var_dump($line->elementObject);
					$line->date_end_planned = dol_mktime('23', '59', '59', 
						GETPOST("dateendp-".$line->id."month"),
						GETPOST("dateendp-".$line->id."day"),
						GETPOST("dateendp-".$line->id."year")
					);
					$line->progress = GETPOST("progress-".$line->id);
					$line->description = GETPOST("descr-".$line->id);

					$line->update($user);
					// on actualise la date de l'élément
					switch ($line->element_type) {
						case 'factory':
							$line->elementObject->set_dateendplanned($user, $line->date_end_planned);
							break;
						case 'order':
							$line->elementObject->setDeliveryDate($user, $line->date_end_planned);
							break;
						case 'proposal':
							$line->elementObject->setDeliveryDate($user, $line->date_end_planned);
							break;
					}
				}
			}
		}
	}

	if ($action == "launchof") {
		// on récupère les données associées à la création des OF
		$datestartmade=dol_mktime(
			GETPOST('madestarthour', 'int'), GETPOST('madestartmin', 'int'), 0,
			GETPOST('madestartmonth', 'int'), GETPOST('madestartday', 'int'), GETPOST('madestartyear', 'int')
		);

		// on regarde les lignes d'of qui sont en attente de lancement
		if (!empty($object->table_element_line)) {
			// Show object lines
			$result = $object->getLinesArray();
			if (!empty($object->lines)) {
				foreach ($object->lines as $line) {
					// si l'on a coche la ligne, on lance l'OF
					if ($line->element_type == 'factory' && GETPOST("chk".$line->id, "int")	== 1) {
						// on lance l'of
						$result=$line->elementObject->set_datestartmade($user, $datestartmade);
					}
				}
			}
		}
		$action="";
	}

	// Link to a project
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProjet(GETPOST('projectid', 'int'));
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	if ($action == "validate" || $action == "reopen") {
		$object->validate($user, 1);
	}

	if ($action == "close") {
		$object->validate($user, 2);
	}

	if ($action == "cancel") {
		$object->validate($user, 9);
	}


    // Action to build doc
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// associer le restock à un tier?
    if ($action == 'set_thirdparty' && $permissiontoadd) {
    	$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'RESTOCK_MODIFY');
    }
    // Actions to send emails
    $triggersendname = 'RESTOCK_SENTBYMAIL';
    $autocopy = 'MAIN_MAIL_AUTOCOPY_RESTOCK_TO';
    $trackid = 'restock'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother= new FormOther($db);
if (!empty($conf->projet->enabled)) { 
	$formproject = new FormProjets($db); 
}


$title=$langs->trans("Restock");
$helpurl='https://wiki.patas-monkey.com/index.php?title=ReStock';
llxHeader('', $title, $helpurl, '');

// Part to edit record USED 
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("restock"));

	print '<form method="POST" action="card.php">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';

	dol_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	if (!empty($object->table_element_line)) {
    	// Show object lines
		$result = $object->getLinesArray();


    	print '<div class="div-table-responsive-no-min">';
    	print '<table id="tablelines" class="noborder noshadow" width="100%">';
    	if (!empty($object->lines)) {
			$product_static = new product($db);

			// entete de ligne
			print '<tr>';
			print '<td width=15%>'.$langs->trans("Element")."</td>";
			print '<td width=15%>'.$langs->trans("ElementInfo")."</td>";
			print '<td width=100px>'.$langs->trans("DateCreation")."</td>";
			print '<td >'.$langs->trans("Description")."</td>";
			print '<td align=center>'.$langs->trans("DateEndPlanned")."</td>";
			print '<td align=right>'.$langs->trans("Progress")."</td>";
			print '<td align=right>'.$langs->trans("Quantity")."</td>";
			print '<td align=right>'.$langs->trans("Controling")."</td>";
			print '<td align=right>'.$langs->trans("Status")."</td>";
			print '</tr>';
			

			foreach ($object->lines as $line) {
				print '<tr valign=top>';
				print '<td>'.$line->elementObject->getNomUrl(1);
				print '</td>';

				print '<td align=left>';
				print "<textarea cols=50 lines=3 name='descr-".$line->id."'>";
				print $line->description;
				print '</textarea>';
				print '</td>';

				print '<td align=left>';
				if($line->element_type == "factory")
					print $langs->trans("Controling")." : ". $line->elementObject->controlingInfos();;
				print '</td>';

				print '<td align=left>';
				switch ($line->element_type) {
					case 'factory':
						$product_static->fetch($line->elementObject->fk_product);
						print $product_static->getNomUrl(1);
						print " - ".$line->elementObject->qty_planned;
						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->datec, 'day');
						break;
					case 'order':
						print $line->elementObject->thirdparty->getNomUrl(1);
						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->date, 'day');
						break;
					case 'proposal':
						print $line->elementObject->thirdparty->getNomUrl(1);
						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->date_creation, 'day');
						break;
				}
				print '</td>';

				print '<td align=right>';
				print $form->selectDate(($line->date_end_planned?$line->date_end_planned:-1), 
					'dateendp-'.$line->id, 0, 0);
				print '</td>';

				print '<td align=right>';
				print $formother->select_percent($line->progress, 'progress-'.$line->id, 0, 5, 0, 100, 1);
				print '</td>';

				print '<td align=right>';
				print $line->quantity;
				print '</td>';

				print '<td align=right>';
				if($line->element_type == "factory")
					print $line->elementObject->controlingInfos();;
				print '</td>';

				print '<td align=right>'.$line->elementObject->getLibStatut(5);
				print '</td>';

				print '</tr>';	
			}
		}

		print '</table>';
		print '</div>';
	}


	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $res = $object->fetch_optionals();

	$head = restockPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("restock"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteRestock'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);


	// Confirmation of action partial
	if ($action == 'finalise') {
		$text=$langs->trans("ConfirmInfoFinalise");
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('FinaliseMovR'), $text, 'confirm_finalise', $formquestion, 0, 1, 220);
	}
	
	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm) ; //, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/restock/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	// Generated Element
	if (is_object($object->elementOrigin))
		$morehtmlref.='<br>'.$langs->trans('Element') . ' : ' .$object->elementOrigin->getNomUrl(1);


	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($action != 'classify')
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		if ($action == 'classify') {
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects(-1, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, 0, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	//$keyforbreak='description';	// We change column just after this field

	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '<table class="border centpercent" >'."\n";
	print '<tr class="liste_titre">';
	print '<td width=50% class="titlefield">'.$langs->trans("Elements")."</td>";
	print '<td width=50px align=right >'.$langs->trans("Nb")."</td>";
	print '<td width=100px align=right  >'.$langs->trans("NbProducts")."</td>";
	print '</tr>';
	$pictoOf = img_picto($langs->trans('Factory'), "object_factory@factory");
	$pictoOrder = img_picto($langs->trans("SupplierOrder"), "object_order");
	$pictoProposal = img_picto($langs->trans("SupplierProposal"), "object_supplier_proposal");

	print '<tr><td>'.$pictoProposal." ".$langs->trans("SupplierProposal").'</td>';
	if ($object->nbproposal >0) 
		print '<td class="right" class="nowrap">'.$object->nbproposal."</td><td  align=right>".$object->sumQtyProposal;
	else
		print '<td colspan=2>';
		print '</td></tr>';
	print '<tr><td>'.$pictoOrder." ".$langs->trans("SupplierOrder").'</td>';
	if ($object->nborder >0) 
		print '<td class="right" class="nowrap">'.$object->nborder."</td><td  align=right>".$object->sumQtyOrder;
	else
		print '<td colspan=2>';
	print '</td></tr>';
	if (!empty($conf->factory->enabled)) {	
		print '<tr><td>'.$pictoOf." ".$langs->trans('Factory').'</td>';
		if($object->nbof >0)
			print '<td class="right" class="nowrap">'.$object->nbof."</td><td align=right>".$object->sumQtyOf;
		else
			print '<td colspan=2>';
		print '</td></tr>';
	}
	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();
		
		$bGenOF = false;
		print '<form>';
		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';
		if (!empty($object->lines)) {
			$product_static = new Product($db);
			$entrepot_static = new Entrepot($db);

			// entete de ligne
			print '<tr>';
			print '<td width=10%>'.$langs->trans("Element")."</td>";
			print '<td >'.$langs->trans("ElementInfos")."</td>";
			print '<td align=center width=100px>'.$langs->trans("DateCreation")."</td>";
			print '<td >'.$langs->trans("Description")."</td>";
			
			print '<td  width=100px align=center>'.$langs->trans("DateEnd")."</td>";
			print '<td align=right>'.$langs->trans("Progress")."</td>";
			print '<td >'.$langs->trans("ElementProduct")."</td>";
			print '<td align=right>'.$langs->trans("Quantity")."</td>";
			print '<td align=right>'.$langs->trans("Status")."</td>";
			print '<td width=70px >';
			if ($object->nbproposal >0) 
				print '<div style="margin-left: 8px;float:right" id=chkProposalAll>'.$pictoProposal.'</div>';
			if ($object->nborder >0) 
				print '<div style="margin-left: 8px;float:right" id=chkOrderAll>'.$pictoOrder.'</div>';
			if ($object->nbof >0) 
				print '  <div style="margin-left: 8px;float:right" id=chkFactoryAll>'.$pictoOf.'</div>';
			print "</td>";
			print '</tr>';
			
			$elemenType="";
			$fk_elementId="";

			foreach ($object->lines as $line) {
				$elemenType = $line->element_type;
				$fk_elementId = $line->fk_elementid;
	
				print '<tr>';
				print '<td>'.$line->elementObject->getNomUrl(1);
				print '</td>';

				print '<td align=left>';

				switch ($line->element_type) {
					case 'factory':
						$entrepot_static->fetch($line->elementObject->fk_entrepot);
						print $entrepot_static->getNomUrl(1);
						print " - ".$line->elementObject->controlingInfos();;

						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->datec, 'day');
						break;
					case 'order':
						print $line->elementObject->thirdparty->getNomUrl(1);
						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->date, 'day');
						break;
					case 'proposal':
						print $line->elementObject->thirdparty->getNomUrl(1);
						print '</td>';
						print '<td align=center>';
						print dol_print_date($line->elementObject->date_creation, 'day');
						break;
				}

				print '</td>';

				print '<td>'.$line->description;
				print '</td>';


				print '<td align=center>';
				switch ($line->element_type) {
					case 'factory':
						print dol_print_date($line->elementObject->date_end_planned, 'day');
						break;
					case 'order':
						print dol_print_date($line->elementObject->date_livraison, 'day');
						break;
					case 'proposal':
						print dol_print_date($line->elementObject->date_livraison, 'day');
						break;
					}
				print '</td>';



				print '<td align=right>';
				print $line->progress;
				print '</td>';

				print '<td align=left>';
				if ($line->fk_product == 0) {
					// on tente de récupérer la ref du produit 
					// pour factory c'est simple
					if ($line->element_type == 'factory') {
						$line->updateProduct($line->elementObject->fk_product);
					} else {
						// pour les autres éléments on regarde en fonction de la quantité
						foreach($line->elementObject->lines as $objectDetail) {
							if ($line->quantity == $objectDetail->qty ) {
								$line->updateProduct($objectDetail->fk_product);
								break;
							}
						}
						// si on a pas trouvé par la quantité, mais qu'il n'y a qu'une ligne de produit dans l'élément
						if ($line->fk_product == 0 && count($line->elementObject->lines)==1) {
							$line->updateProduct($line->elementObject->lines[0]->fk_product);
						}
					}
				}
				if ($line->fk_product > 0) {
					$product_static->fetch($line->fk_product);
					print $product_static->getNomUrl(1);
				} 
				print '</td>';

				print '<td align=right>';
				print $line->quantity;
				if ($line->element_type == 'factory') {
					if ($line->elementObject->qty_planned != $line->quantity)
						print " / ".$line->elementObject->qty_planned;
				} else {
					foreach($line->elementObject->lines as $objectDetail) {
						// si on est sur la bonne ligne
						if ($line->fk_product == $objectDetail->fk_product) {
							// si la quantité est différente
							if( $line->quantity != $objectDetail->qty ) {
								print " / ". $objectDetail->qty;
								break;
							}
						}
					}
				}
				print '</td>';

				print '<td align=right>'.$line->elementObject->getLibStatut(5);
				print '</td>';

				print '<td align=right>';
				//var_dump($line->elementObject);
				if (($line->element_type == 'proposal' && $line->elementObject->statut == 0)
				|| ($line->element_type == 'order' && $line->elementObject->statut == 0)
				|| ($line->element_type == 'factory' && $line->elementObject->fk_statut == 0)) {
					if ($line->element_type == 'factory') 
						$bGenOF = true;
					print '<input type=checkbox class="chk'.$line->element_type.'" name=chk'.$line->id.' value=1>';
				}
				print '</td>';

				print '</tr>';	
			}
		}

		print '</table>';
		print '</div>';

		print '<div class="fichecenter">';
		if($object->nbof > 0 && $bGenOF) {
			$langs->load("factory@factory");
			print '<div class="fichehalfleft">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="launchof">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<table width=100%>';
			print '<tr class="liste_titre">';
			print '<th colspan=2 align=center>'.$langs->trans("NewOrderBuild").'</th>';
			print '</tr>';
			print '<tr><td width=25% align=left>';
			print $langs->trans("FactoryDateStartMade");
			print '</td><td>';
			print $form->selectDate("", 'madestart', 1, 1);
			print '</td></tr>';

			print '</table>';
			print '<div class="tabsAction">'."\n";
			print '<input class=butAction type=submit value="'.$langs->trans("LaunchOF").'">';
			print '</div>';
			print '</div>';
		}

		print '<div class="fichehalfright">';
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {

			if ($object->status == $object::STATUS_DRAFT) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate">'.$langs->trans("Validate").'</a>';
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit">'.$langs->trans("Edit").'</a>';
			}
			if ($object->status == $object::STATUS_VALIDATE) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close">'.$langs->trans("Close").'</a>';
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit">'.$langs->trans("Edit").'</a>';
			}
			if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATE) 
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=cancel">'.$langs->trans("Desactivated").'</a>';
			if ($object->status == $object::STATUS_CANCELED || $object->status == $object::STATUS_CLOSE) 
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen">'.$langs->trans("ReOpen").'</a>';
		}
		print '</div>'."\n";
		print '</div>'."\n";
		print '</form>';
	}


	// Buttons for actions

	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre
	$object->modelpdf = $conf->global->RESTOCK_ADDON_PDF;
	// Documents
	$objref = dol_sanitizeFileName($object->ref);
	$relativepath = $objref . '/' . $objref . '.pdf';
	$filedir = $conf->restock->dir_output . '/' . $objref;
	$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	$genallowed = $user->rights->restock->lire;	// If you can read, you can build the PDF to read content
	$delallowed = $user->rights->restock->delete;	// If you can create/edit, you can remove a file on card
	print $formfile->showdocuments('restock', $objref, $filedir, 
				$urlsource, $genallowed, $delallowed, 
				$object->modelpdf, 1, 0, 0, 28, 0, '', '', '', 
				$langs->defaultlang
	);



}

print "<script>
$(document).ready(function() {
	$('#chkFactoryAll').click(function(event) {  //on click 
		$('.chkfactory').each(function() { //loop through each checkbox
			this.checked = !this.checked;  
		});
	});
	$('#chkOrderAll').click(function(event) {  //on click 
		$('.chkorder').each(function() { //loop through each checkbox
			this.checked = !this.checked;  
		});
	});
	$('#chkProposalAll').click(function(event) {  //on click 
		$('.chkproposal').each(function() { //loop through each checkbox
			this.checked = !this.checked;  
		});
	});

});
</script>";

// End of page
llxFooter();
$db->close();