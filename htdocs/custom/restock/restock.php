<?php
/* Copyright (C) 2013-2023		Charlene BENKE		<charlene@patas-monkey.com>
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
 *  \file	   htdocs/restock/restock.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../main.inc.php")) 
	$res=@include("../main.inc.php");		// For root directory
if (! $res && file_exists("../../main.inc.php"))
	$res=@include("../../main.inc.php");	// For "custom" directory

dol_include_once('/restock/class/restock.class.php');
dol_include_once('/restock/core/lib/restock.lib.php');

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->load("bills");


// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->socid) $socid=$user->socid;
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

$action=GETPOST("action");

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"])) {
	$snom = "";
	$search_categ = 0;
	$search_fourn = 0;
	$selProposal =  "";
	$selOrder = "";
	$selFactory = "";
	$selOnlyWithQty = "";
	$filtered = "";
} else {
	$snom = "";
	$search_categ = GETPOST("search_categ", 'int');
	$search_fourn = GETPOST("search_fourn", 'int');
	$selProposal = GETPOST('selproposal', 'int');
	$selOrder = GETPOST('selorder', 'int');
	$selFactory = GETPOST('selfactory', 'int');
	$selOnlyWithQty = GETPOST("selonlywithqty", 'int');
	$filtered = GETPOST("filtered", 'int');
}

/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

if ( isset($_POST['reload']) ) $action = 'restock';

$title=$langs->trans("Restock");
$helpurl='https://wiki.patas-monkey.com/index.php?title=ReStock';
if ($action=="") {
	llxHeader('', $title, $helpurl, '');
	
	// premier écran la sélection des produits

	$param =($search_fourn?"&;search_fourn=".$search_fourn:"");
	$param.=($search_categ?"&search_categ=".$search_categ:"");
	$param.=isset($type)?"&type=".$type:"";

	print_barre_liste($title, 0, "restock.php" );

	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="">';
	print '<input type="hidden" name="filtered" value="1">';

	
	// Filter on categories
	$filtercateg = "";
	if (! empty($conf->categorie->enabled)) {
		$filterCateg = $langs->trans('Categories').' : ';
		$filterCateg.= $htmlother->select_categories(0, $search_categ, 'search_categ', 1);
	}
	
	$filterFourn = "";
	if (! empty($conf->fournisseur->enabled)) {
		$fournisseur=new Fournisseur($db);
		$tblfourn=$fournisseur->ListArray();
		
		$filterFourn.=$langs->trans('Supplier').' : ';
		$filterFourn.=select_fournisseur($tblfourn, $search_fourn, 'search_fourn');
	}
	
	$selected="";
	if ($selOnlyWithQty == 1 )
		$selected = ' checked ';
	$filterSelOnlyWithQty= '<input title="'.$langs->trans("DisplayOnlyWithQty").'"type=checkbox name=selonlywithqty '.$selected.' value=1>';
	$filterSelOnlyWithQty.= $langs->trans('SelectOnlyWithQty');

	$filterElement = $langs->trans('Elements').' : ';
	if (!empty($conf->supplier_proposal->enabled)) {
		$selected="";
		if ($selProposal == 1 || empty($filtered))
			$selected = ' checked ';
		$filterElement.= '<input title="'.$langs->trans("AllowSupplierProposal").'"type=checkbox name=selproposal '.$selected.' value=1>';
		$filterElement.= img_picto($langs->trans("AllowSupplierProposal"), "object_supplier_proposal");
	}
	$selected="";
	if ($selOrder == 1 || empty($filtered))
		$selected = ' checked ';
	$filterElement.= '&nbsp;<input title="'.$langs->trans("AllowSupplierOrder").'" type=checkbox name=selorder '.$selected.' value=1>';
	$filterElement.= img_picto($langs->trans("AllowSupplierOrder"), "object_order");
	if (!empty($conf->factory->enabled)) {
		$selected="";
		if ($selFactory == 1 || empty($filtered))
			$selected = ' checked ';	
		$filterElement.= '&nbsp;<input title="'.$langs->trans("AllowFactory").'" type=checkbox name=selfactory '.$selected.'  value=1>';
		$filterElement.= img_picto($langs->trans('AllowFactory'), "object_factory@factory");
	}

	// tableau de filtrage des OF
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" >'.$filterCateg.'</td>';
	print '<td class="liste_titre" >'.$filterFourn.'</td>';
	print '<td class="liste_titre" >'.$filterSelOnlyWithQty.'</td>';
	print '<td align=right class="liste_titre" >'.$filterElement.'</td>';
	print '</td><td width=60px align=right>';
	print '<input type="image" class="liste_titre" name="button_search"';
	print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter"';
	print ' src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png"';
	print ' value="'.dol_escape_htmltag($langs->trans("Search")).'"';
	print ' title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';
	print '</table>';

	// détermination du besoin
	$select0propals=!empty($conf->global->RESTOCK_PROPOSAL_DRAFT)?$conf->global->RESTOCK_PROPOSAL_DRAFT:0;
	$select1propals=!empty($conf->global->RESTOCK_PROPOSAL_VALIDATE)?$conf->global->RESTOCK_PROPOSAL_VALIDATE:0;
	$select2propals=!empty($conf->global->RESTOCK_PROPOSAL_SIGNED)?$conf->global->RESTOCK_PROPOSAL_SIGNED:0;
	$select0commandes=!empty($conf->global->RESTOCK_ORDER_DRAFT)?$conf->global->RESTOCK_ORDER_DRAFT:0;
	$select1commandes=!empty($conf->global->RESTOCK_ORDER_VALIDATE)?$conf->global->RESTOCK_ORDER_VALIDATE:0;
	$select2commandes=!empty($conf->global->RESTOCK_ORDER_PARTIAL)?$conf->global->RESTOCK_ORDER_PARTIAL:0;
	$select0factures=!empty($conf->global->RESTOCK_BILL_DRAFT)?$conf->global->RESTOCK_BILL_DRAFT:0;
	$select1factures=!empty($conf->global->RESTOCK_BILL_VALIDATE)?$conf->global->RESTOCK_BILL_VALIDATE:0;
	$select2factures=!empty($conf->global->RESTOCK_BILL_PARTIAL)?$conf->global->RESTOCK_BILL_PARTIAL:0;
	
	$restock_static=new Restock($db);
	$tblRestock=array();

	//$lgnRestock->nbCmdeDraft=0;
	//$lgnRestock->nbCmdeValidate=0;
	//$lgnRestock->nbCmdePartial=0;
	//$lgnRestock->nbPropDraft=0;
	//$lgnRestock->nbPropValidate=0;
	//$lgnRestock->nbPropSigned=0;
	//$lgnRestock->nbBillDraft=0;
	//$lgnRestock->nbBillValidate=0;
	//$lgnRestock->nbBillPartial=0;

	// on récupère les produits présents dans les commandes et les propales
	if ($select0propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 1);
	if ($select2propals > 0)
		$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 2);
	if ($select0commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 1);
	if ($select2commandes > 0)
		$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 2);
	if ($select0factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 0);
	if ($select1factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 1);
	// paié partiellement
	if ($select2factures > 0)
		$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 3);

	// on gère la décomposition des produits
	$tblRestockTemp=array();

	foreach ($tblRestock as $lgnRestock) {

		// on récupère la composition et les quantités
		$tbllistofcomponent=$restock_static->getcomponent($lgnRestock->id, 1, $search_categ, $search_fourn);

		$numlines=count($tblRestockTemp);
		foreach ($tbllistofcomponent as $lgncomponent) {
			$lineofproduct = -1;

			// on regarde si on trouve déjà le produit dans le tableau 
			for ($j = 0 ; $j <= $numlines ; $j++)
				if ($tblRestockTemp[$j]->id == $lgncomponent[0])
					$lineofproduct=$j;

			if ($lineofproduct >= 0) {
				// on multiplie par la quantité du composant
				$tblRestockTemp[$lineofproduct]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$lineofproduct]->nbCmdeValidate = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$lineofproduct]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$lineofproduct]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$lineofproduct]->nbPropValidate = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$lineofproduct]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$lineofproduct]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$lineofproduct]->nbBillValidate = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$lineofproduct]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
			} else {
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$tblRestockTemp[$numlines]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$numlines]->nbCmdeValidate  = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$numlines]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$numlines]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$numlines]->nbPropValidate  = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$numlines]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$numlines]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$numlines]->nbBillValidate  = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$numlines]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
				$numlines++;
			}
		}
	}

	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);

	print '</form>';
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre border" align=center colspan="4">'.$langs->trans("InfoProduct").'</td>';
	if ($select0propals+$select1propals+$select2propals > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InPropal").'</td>';
	if ($select0commandes+$select1commandes+$select2commandes > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InOrder").'</td>';
	if ($select0factures+$select1factures+$select2factures > 0)
		print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InBill").'</td>';
	print '</td><td colspan="2" align=center>'.$langs->trans("RestockStock");
	//print '</td><td align=center>'.$langs->trans("RestockStockAlertAbrev");
	print '</td><td align=right>'.$langs->trans("AlreadyOrder1");
	print '</td><td align=center>'.$langs->trans("Qty").'</td></tr>';

	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	if ($select0propals+$select1propals+$select2propals > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockValidate").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockSigned").'</td>';
	}
	if ($select0commandes+$select1commandes+$select2commandes > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockValidate").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("ActionsRunningshort").'</td>';
	}
	if ($select0factures+$select1factures+$select2factures > 0) {
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockDraft").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("BillsUnpaid").'</td>';
		print '<td class="liste_titre border" align="center">'.$langs->trans("RestockPaid").'</td>';
	}
	print '<td class="liste_titre" align="right">'.$langs->trans("Physical").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("RestockStockLimitAbrev").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";



	$idprodlist="";
	$product_static=new Product($db);
	foreach ($tblRestock as $lgnRestock) {

		// si factory est actif on vérifie la composition du produit
		if (!empty($conf->factory->enabled)) {
			dol_include_once('/factory/class/factory.class.php');
			$factory_static = new Factory($db);
			$lgnRestock->nbcomposed = count($factory_static->getChildsArbo($lgnRestock->id));
		}

		$bDisplayLine=false;
		// si le produit est à l'achat ou est composé, il est affichable dans restock
		if ($lgnRestock->onBuyProduct == 1 || $lgnRestock->nbcomposed > 0) {
			// selon le paramétrage selon le type de produit/service
			if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==1 && $lgnRestock->fk_product_type=="0") 
				$bDisplayLine=true;
			if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==2 && $lgnRestock->fk_product_type=="1")
				$bDisplayLine=true;
			if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==0)
				$bDisplayLine=true;

			if ($bDisplayLine) {

				// seconde étape de sélection selon les modes de filtrage
				$product_fourn = new ProductFournisseur($db);
				$product_fourn_list = $product_fourn->list_product_fournisseur_price($lgnRestock->id, "", "");
				
				if (!empty($filtered)) {
					$bDisplayLine = false;
					// si c'est un produit composé et factory actif
					if ($lgnRestock->nbcomposed > 0 && $selFactory)
						$bDisplayLine = true;

					// si pas de prix fournisseur et ce se n'est pas un produit composé
					if (count($product_fourn_list) == 0 && $lgnRestock->nbcomposed == 0 && $selProposal)
						$bDisplayLine = true;

					// si c'est un produit commandable
					if (count($product_fourn_list) > 0 && $selOrder)
						$bDisplayLine = true;
				}

				// détermination des quantités nécessaires
				$estimedNeed=0;
				$estimedNeed+=$lgnRestock->nbPropDraft*$select0propals/100;
				$estimedNeed+=$lgnRestock->nbPropValidate*$select1propals/100;;
				$estimedNeed+=$lgnRestock->nbPropSigned*$select2propals/100;;
				$estimedNeed+=$lgnRestock->nbCmdeDraft*$select0commandes/100;
				$estimedNeed+=$lgnRestock->nbCmdeValidate*$select1commandes/100;
				$estimedNeed+=$lgnRestock->nbCmdePartial*$select2commandes/100;
				$estimedNeed+=$lgnRestock->nbBillDraft*$select0factures/100;
				$estimedNeed+=$lgnRestock->nbBillValidate*$select1factures/100;
				$estimedNeed+=$lgnRestock->nbBillpartial*$select2factures/100;
				// si on affiche que les produits avec des quantités calculée
				if ($selOnlyWithQty == 1 && $estimedNeed == 0)
					$bDisplayLine = false;

			}

		}

		if ($bDisplayLine) {


			print "<tr >";
			$idprodlist.=$lgnRestock->id."-";
			print '<td class="nowrap">';
			$product_static->fetch($lgnRestock->id);
			print $product_static->getNomUrl(1, '', 24);
			if ($lgnRestock->nbcomposed > 0) {
				print '('.$lgnRestock->nbcomposed.')';
				print '<input type=hidden name="factory-'.$lgnRestock->id.'" value="1">';
			}

			print '</td>';
			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			print '<td align="right">'.price($lgnRestock->prixVenteHT).'</td>';
			print '<td align="right">'.price($lgnRestock->prixAchatHT).'</td>';
			if ($select0propals+$select1propals+$select2propals > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbPropDraft.'</td>';		
				print '<td align="right" class="border">'.$lgnRestock->nbPropValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbPropSigned.'</td>';		
			}
			if ($select0commandes+$select1commandes+$select2commandes > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbCmdeDraft.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbCmdeValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbCmdePartial.'</td>';
			}
			if ($select0factures+$select1factures+$select2factures > 0) {
				print '<td align="right" class="border">'.$lgnRestock->nbBillDraft.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbBillValidate.'</td>';
				print '<td align="right" class="border">'.$lgnRestock->nbBillPartial.'</td>';
			}
			print '<td align="right">'.$lgnRestock->stockQty.'</td>';
			print '<td align="right">'.$lgnRestock->stockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';


			// si on travail en réassort, on ne prend pas en compte le stock et les commandes en cours
			if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3) {
				$estimedNeed-= $lgnRestock->stockQty ;

			}
			if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3) {
				$estimedNeed-= $lgnRestock->nbCmdFourn;

			}
			//si il y a encore du besoin, (on a vidé toute le stock et les commandes)
			if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
				if (($estimedNeed > 0) && ($lgnRestock->stockQtyAlert > 0))
					$estimedNeed+= $lgnRestock->stockQtyAlert;

			// si le besoin est négatif cela signifie que l'on a assez , pas besoin de commander
			if ($estimedNeed < 0) 
				$estimedNeed = 0;

			print '<td align="right" class="nowrap">';
			// seulement pour les produits
			//if ($product_static->type == 0) {
				// on indique que le produit n'a pas de fournisseur
				if (!empty($conf->supplier_proposal->enabled)) {
					// demande de prix seulement si il n'y a pas de prix fournisseur
					if (count($product_fourn_list) == 0) {
						// si c'est un produit composé
						if ($lgnRestock->nbcomposed > 0)
							print img_picto("Factory", "object_factory@factory");
						else
							print img_picto($langs->trans("SupplierProposal"), "object_supplier_proposal");
					} else
						print img_picto($langs->trans("SupplierOrder"), "object_order");
					print '&nbsp;<input type=text size=1 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'">';
				} else {
					if (count($product_fourn_list) > 0 ) {
						print img_picto($langs->trans("SupplierOrder"), "object_order");
						print '&nbsp;<input type=text size=1 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'">';
					} else {
						// si c'est un produit composé et pas de demande de prix
						if ($lgnRestock->nbcomposed > 0) {
							print img_picto("Factory", "object_factory@factory");
							print '&nbsp;<input type=text size=1 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'">';
						} else
							print $langs->trans("NoFournishForProduct");
					}
				}
				print '<input type=hidden name="composed-'.$lgnRestock->id.'" value="'.$lgnRestock->nbcomposed.'">';
			//}
			print "</td></tr>\n";
		}
	}
	print '</table>';
	// pour mémoriser les produits à restockvisionner
	// on vire le dernier '-' si la prodlist est alimenté
	if ($idprodlist)
		$idprodlist=substr($idprodlist, 0, -1);
	print '<input type=hidden name="prodlist" value="'.$idprodlist.'"></td>';	
	
	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	if (!empty($conf->supplier_proposal->enabled)) {
		print '<input title="'.$langs->trans("AllowSupplierProposal").'"type=checkbox name=chkproposal checked value=1>';
		print img_picto($langs->trans("AllowSupplierProposal"), "object_supplier_proposal");
	}
	print '&nbsp;<input title="'.$langs->trans("AllowSupplierOrder").'" type=checkbox name=chkorder checked value=1>';
	print img_picto($langs->trans("AllowSupplierOrder"), "object_order");
	if (!empty($conf->factory->enabled)) {
		print '&nbsp;<input title="'.$langs->trans("AllowFactory").'" type=checkbox name=chkfactory checked value=1>';
		print img_picto($langs->trans('AllowFactory'), "object_factory@factory");
	}
	print '<br><input type="submit" class="butAction" name="bouton" value="'.$langs->trans('RestockOrder').'">';
	print '</div >';
	print '</form >';
} elseif ($action=="restock") {
	llxHeader('', $title, $helpurl, '');

	// ajouter un titre

	// deuxieme étape : la sélection des fournisseurs
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';
	print '<input type="hidden" name="chkproposal" value="'.GETPOST("chkproposal").'">';
	print '<input type="hidden" name="chkorder" value="'.GETPOST("chkorder").'">';
	print '<input type="hidden" name="chkfactory" value="'.GETPOST("chkfactory").'">';

	print '<table class="liste" width="100%">';
	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("QtyRestock").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("FournishSelectInfo").'</td>';
	print "</tr>\n";
	$product_static=new Product($db);
	
	$tblproduct=explode("-", GETPOST("prodlist"));
	// pour chaque produit présent
	foreach ($tblproduct as $idproduct) {
		$nbProd=GETPOST("prd-".$idproduct);
		$nbComposed=GETPOST("composed-".$idproduct);
		// si on ne demande pas les of
		if ($nbComposed >0 && empty(GETPOST("chkfactory")))
			$nbProd=0;

		// on récupère les infos fournisseurs
		$product_fourn = new ProductFournisseur($db);
		$product_fourn_list = $product_fourn->list_product_fournisseur_price($idproduct, "", "");
		if (is_array($product_fourn_list)) {
			// si pas de fournisseur et demande de prix désactivé
			if (count($product_fourn_list) == 0 && $nbComposed == "" && empty(GETPOST("chkproposal"))) 
				$nbProd=0;
			
			// si fournisseur et commande fournisseur désactivé
			if (count($product_fourn_list) > 0 && $nbComposed == "" && empty(GETPOST("chkorder")) && empty(GETPOST("chkproposal"))) 
				$nbProd=0;
		}

		$totHT=0;
		$totTTC=0;
		
		if ($nbProd > 0) {
			print "<tr>";
			print '<td class="nowrap" valign=top>';
			$product_static->id = $idproduct;
			$product_static->fetch($idproduct);
			print $product_static->getNomUrl(1, '', 24);
			if ($nbComposed>0) {
				print " (".$nbComposed.")";
			}
			print '</td>';
			print '<td valign=top>'.$product_static->label.'</td>';
			print '<td align=center valign=top>';
			print "<input type=text size=4 name='prd-".$idproduct."' value='".$nbProd."'>";
			print '<input type=hidden name="composed-'.$idproduct.'" value="'.$nbComposed.'">';
			print '</td><td width=60%>';

			//si il y a des demandes de prix activés
			if (!empty($conf->supplier_proposal->enabled) && GETPOST("chkproposal") &&  $nbComposed=="") {
				print img_picto($langs->trans("SupplierProposal"), "object_supplier_proposal");
				print "&nbsp;".$langs->trans("SupplierProposal")." ";
				print select_fournisseur( GETPOST('askpriceid-'.$idproduct, "array"), 'askpriceid-'.$idproduct,  0,  '',  '', 0, '',  false,  0, 'style="width: 60%"', '', true);
			}

			if (count($product_fourn_list) > 0 && GETPOST("chkorder")) {
				print '<table class="liste" width="100%">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">';
				print img_picto($langs->trans("SupplierOrder"), "object_supplier_order");
				print "&nbsp;".$langs->trans("SupplierOrder").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Ref").'</td>';
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) 
					print '<td class="liste_titre">'.$langs->trans("Availability").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("QtyMinAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("VAT").'</td>';

				// Charges ????
				print '<td class="liste_titre" align="right">'.$langs->trans("UnitPriceHTAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("HT").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("TTC").'</td>';
				print "</tr>\n";
			

				// pour chaque fournisseur du produit
				foreach ($product_fourn_list as $productfourn) {
					print "<tr ><td>";
					$presel=false;
					if ($nbProd < $productfourn->fourn_qty) {	
						// si on est or seuil de quantité on désactive le choix
						print '<input type=radio disabled title="'.$langs->trans("NotEnoughOrder").'">&nbsp;';
					} else {
						// on mémorise à la fois l'id du fournisseur et l'id du produit du fournisseur
						$valueCheck = $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id;
						// le taux de tva et le pourcentage de remise...
						$valueCheck.= '-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent;
						if (count($product_fourn_list) > 1) {
							// on revient sur l'écran avec une préselection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $valueCheck) {
								$presel=true;
								$checked = " checked=true ";
							}
							print '<input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$valueCheck.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '	<input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$valueCheck.'">&nbsp;';
						}
					}
					print $productfourn->getSocNomUrl(1, 'supplier').'</td>';

					// Supplier
					print '<td align="left">'.$productfourn->fourn_ref;
					print ($productfourn->supplier_reputation?' ('.$langs->trans($productfourn->supplier_reputation).')':"");
					print '</td>';

					//Availability
					if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) {
						$form->load_cache_availability();
						$availability= $form->cache_availability[$productfourn->fk_availability]['label'];
						print '<td align="left">'.$availability.'</td>';
					}

					// Quantity
					print '<td align="right">';
					print $productfourn->fourn_qty;
					print '</td>';

					// VAT rate
					print '<td align="right">';
					print vatrate($productfourn->fourn_tva_tx, true);
					print '</td>';

					// Unit price
					print '<td align="right">';
					if ($productfourn->fourn_remise_percent)
						$unitprice = $productfourn->fourn_unitprice * (1-($productfourn->fourn_remise_percent/100));
					elseif ($productfourn->fourn_remise)
						$unitprice = $productfourn->fourn_unitprice -$productfourn->fourn_remise;
					else 
						$unitprice = $productfourn->fourn_unitprice;
					print price($unitprice);
					//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
					print '</td>';	

					// Unit Charges ???
					if (! empty($conf->margin->enabled))
						$unitcharge=($productfourn->fourn_unitcharges ? 
										price($productfourn->fourn_unitcharges) : 
										($productfourn->fourn_qty ? 
														price($productfourn->fourn_charges/$productfourn->fourn_qty):
														"&nbsp;"
										)
						);
					if ($nbProd < $productfourn->fourn_qty)
						$nbProd = $productfourn->fourn_qty;

					$estimatedFournCost=$nbProd*$unitprice+($unitcharge!="&nbsp;"?$unitcharge:0);
					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if ($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel==true) {
						$totHT=$totHT+$estimatedFournCost;
						$totTTC=$totTTC+$estimatedFournCostTTC;
					}
					print '</tr>';
				}
				print "</table>";
			} else {
				// si on a juste les commandes fournisseurs
				if (!$conf->supplier_proposal->enabled && $nbComposed=="")
					print $langs->trans("NoFournishForThisProduct");

				// si il s'agit d'une fabrication
				if ($nbComposed>0 ) {
					$isFactory=true;
					$checked="";
					if (GETPOST('factory-'.$idproduct, 'int') == 1)
						$checked="checked=true";
					print img_picto("Factory","object_factory@factory");
					print '&nbsp;<input type=checkbox '.$checked.' name="factory-'.$idproduct.'" value="1">&nbsp;';
					print $langs->trans("CreateOF");
					$formProduct_static = new FormProduct($db);
					print ' - '.$langs->trans("EntrepotStock").' :';
					print $formProduct_static->selectWarehouses(
							GETPOST('fk_entrepot-'.$idproduct, 'int'), 
							"fk_entrepot-".$idproduct, 'warehouseopen'
					);
				}
			}
			print '</td>';
			print '</tr>';
		}
	}
	print '<tr >';
	print '<td colspan=2></td><td align=right>';
	print '<input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'"></td>';
	print '<td><table width=100% ><tr><td ></td>';
	print '<td width=100px align=left>'.$langs->trans("AmountHT")." : <br>";
	print $langs->trans("AmountVAT")." : ".'</td>';
	print '<td width=100px align=right>'.price($totHT)." ".$langs->trans("Currency".$conf->currency);
	print '<br>'.price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';	
	print '</table>';
	print '</td></tr>';	
	print '</table>';
	
	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<table width=75%>';
	print '<tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td><td align=left width=200px>';
	// on mémorise la référence du de la facture client sur la commande fournisseur
	print '<input type=text size=40 name=reforderfourn';
	print ' value="'.$langs->trans('Restockof').'&nbsp;'.dol_print_date(dol_now(), "%d/%m/%Y").'"></td>';
	print '<td align=right>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'">';
	print '</td></tr></table>';
	print '</div >';
	print '</form >';
} elseif ($action=="createrestock") {
	// dernière étape : la création des commande fournisseur
	// on récupère la liste des produits à commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utiliser un tableau pour stocker les commandes, propale fournisseurs et OF	
	$tblCmdeFourn=array();
	$tblPropalFourn=array();
	$tblFactory=array();

	// on parcourt les produits pour récupérer les fournisseurs, les produits et les quantités
	foreach ($tblproduct as $idproduct) {
		// on regarde à quoi on a affaire
		// Si c'est un OF
		if (GETPOST("composed-".$idproduct)) {
			// si on lance l'of
			if (GETPOST('factory-'.$idproduct)) {
				$numlinesOF=count($tblFactory);
				$tblFactory[$numlinesOF][0] = $idproduct;
				$tblFactory[$numlinesOF][1] = GETPOST("prd-".$idproduct);
				$tblFactory[$numlinesOF][2] = GETPOST("fk_entrepot-".$idproduct);
			}
		}
		// si c'est une demande de prix
		if (GETPOST('askpriceid-'.$idproduct, "array")) {
			$tblFournisseurPropal=GETPOST('askpriceid-'.$idproduct, "array");
			$numlinesPropal=count($tblPropalFourn);
			$lineOfPropalFourn = -1;
			foreach ($tblFournisseurPropal as $supplierid) {
				// on regarde si le fournisseur n'est pas déjà identifié
				for ($j = 0 ; $j < $lineOfPropalFourn ; $j++)
					if ($tblPropalFourn[$j][0] == $supplierid)
						$lineOfPropalFourn =$j;
		
				// si le fournisseur n'est pas déja dans le tableau des fournisseurs
				if ($lineOfPropalFourn == -1) {
					$tblPropalFourn[$numlinesPropal][0] = $supplierid;
					$tblPropalFourn[$numlinesPropal][1] = array(array(
									$idproduct, GETPOST("prd-".$idproduct)
						));
				} else {
					$tblPropalFourn[$lineOfPropalFourn][1] = array_merge(
							$tblPropalFourn[$lineOfPropalFourn][1], array(array(
									$idproduct, GETPOST("prd-".$idproduct)
						))
					);
				}
			}

		} else {
			// sinon c'est une commande fournisseur alors
			$numlinesFourn=count($tblCmdeFourn);
			$lineOfFourn = -1;
			if (GETPOST("fourn-".$idproduct)) {
				$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
				// 0 : id du fournisseur
				// 1 : id du prix fournisseur
				// 2 : tx de tva du produit pour le fournisseur
				// 3 : tx de la remise du produit 

				if ($tblfourn[0]) {
					// on regarde si le fournisseur n'est pas déjà identifié
					for ($j = 0 ; $j < $numlinesFourn ; $j++)
						if ($tblCmdeFourn[$j][0] == $tblfourn[0])
							$lineOfFourn =$j;
			
					// si le fournisseur n'est pas déja dans le tableau des fournisseurs
					if ($lineOfFourn == -1) {
						$tblCmdeFourn[$numlinesFourn][0] = $tblfourn[0];
						$tblCmdeFourn[$numlinesFourn][1] = array(array(
										$idproduct, 
										GETPOST("prd-".$idproduct), 
										$tblfourn[1], $tblfourn[2], $tblfourn[3]
						));
					} else {
						$tblCmdeFourn[$lineOfFourn][1] = array_merge(
										$tblCmdeFourn[$lineOfFourn][1],
										array(array($idproduct, GETPOST("prd-".$idproduct), 
										$tblfourn[1], $tblfourn[2], $tblfourn[3]))
						);
					}
				}
			}
		}
	}
	
	// on commence par creer un Restock
	$newRestock = new Restock($db);
	$newRestock->label =  GETPOST("reforderfourn");
	$newRestock->status= Restock::STATUS_DRAFT;
	$newRestock->fk_originid = $id;
	$newRestock->origin_type = 'product';
	$restockId = $newRestock->create($user);

	if ($restockId > 0) {
		// on lance les créations des OF
		if ($tblFactory) {
			dol_include_once('/factory/class/factory.class.php');
			$factory_static = new Factory($db);
			foreach ($tblFactory as $factoryProductId) {
				$factoryId = 0;
				if ($conf->global->RESTOCK_FILL_FACTORY_DRAFT > 0) {
					$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'factory';
					$sql.= ' WHERE fk_product = '.$factoryProductId[0];
					$sql.= ' AND fk_entrepot='.$factoryProductId[2];
					$sql.= ' AND fk_statut = 0';
					$sql.= ' AND entity='.$conf->entity;
					if  (	$conf->global->RESTOCK_FILL_FACTORY_DRAFT == 2
						||	$conf->global->RESTOCK_FILL_FACTORY_DRAFT == 4)
						$sql.= ' AND fk_user_author='.$user->id;
						
					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						$factoryId = $objp->rowid;
						$factory_static->fetch($factoryId);
						$factory_static->set_qtyplanned($user, $factory_static->qty_planned + $factoryProductId[1]);
					}
					// on modifie la quantité à produire (et les quantité de composants?)
				}
				// on cree autant d'of que nécessaire
				if($factoryId == 0) {
					$factory_static->fk_product = $factoryProductId[0];
					$factory_static->qty_planned = $factoryProductId[1];
					$factory_static->fk_entrepot = $factoryProductId[2];
					$factory_static->description = GETPOST("reforderfourn");
					$factoryId = $factory_static->createof();
				}

				$ret = $factory_static->add_object_linked("restock", $restockId);

				// on ajoute une ligne dans le détail du restock associé à l'OF
				$newRestock->addLine($restockId, $factoryId, 'factory',  $factoryProductId[1], 0);
			}
		}

		// on va maintenant créer les commandes fournisseurs
		if ($tblCmdeFourn) {
			// V8 Bullchit
			$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;
			// on va maintenant créer les commandes fournisseurs
			foreach ($tblCmdeFourn as $cmdeFourn) {
				$idCmdFourn = 0;
				// si il on charge les commandes fournisseurs brouillons
				if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
					// on vérifie qu'il n'y a pas une commande fournisseur déjà active
					$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'commande_fournisseur as cof';
					$sql.= ' WHERE fk_soc='.$cmdeFourn[0];
					$sql.= ' AND fk_statut=0';
					$sql.= ' AND entity='.$conf->entity;
					if  (	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 2
						||	$conf->global->RESTOCK_FILL_ORDER_DRAFT == 4)
						$sql.= ' AND fk_user_author='.$user->id;
						
					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						$idCmdFourn = $objp->rowid;
					}
					$objectcf = new CommandeFournisseur($db);
					$objectcf->fetch($idCmdFourn);
					// si il y en a un on ajoute un lien au restock
					$ret = $objectcf->add_object_linked("restock", $restockId);
				}

				// en création
				if ($idCmdFourn == 0) {
					$objectfournisseur = new Fournisseur($db);
					$objectfournisseur->fetch($cmdeFourn[0]);

					$objectcf = new CommandeFournisseur($db);
					$objectcf->ref_supplier		= GETPOST("reforderfourn");
					$objectcf->socid			= $cmdeFourn[0];
					$objectcf->note_private		= '';
					$objectcf->note_public		= '';

					$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
					$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;
					// on fait lien après le create
					$objectcf->origin = "";
					$objectcf->origin_id = 0;

					$idCmdFourn = $objectcf->create($user);
					$ret = $objectcf->add_object_linked("restock", $restockId);

				}

				// V8 Bullchit
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;
				$totalqty=0;
				// ensuite on boucle sur les lignes de commandes
				foreach ($cmdeFourn[1] as $lgnCmdeFourn) {
					$idlgnFourn = 0;
					// on vérifie qu'il n'y a pas déjà une ligne de commande pour ce produit
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet as cofd';
					$sql.= ' WHERE fk_commande='.$idCmdFourn;
					$sql.= ' AND fk_product='.$lgnCmdeFourn[0];
					$resql = $db->query($sql);

					if ($resql) {
						$objp = $db->fetch_object($resql);
						$idlgnFourn = ($objp->rowid?$objp->rowid:0);
					}
					$totalqty+=$lgnCmdeFourn[1];
					// si pas de ligne existante ou création d'une ligne à chaque fois
					if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
						// on cree la commande fournisseur
						$result=$objectcf->addline(
										'', 0,
										$lgnCmdeFourn[1],	// $qty
										$lgnCmdeFourn[3],	// TxTVA
										0, 0,
										$lgnCmdeFourn[0],	// $fk_product
										$lgnCmdeFourn[2],	// $fk_prod_fourn_price
										0, 					// $fourn_ref
										$lgnCmdeFourn[4],	// $remise_percent
										'HT',				// $price_base_type
										0, 0, 0,			// pu_ttc ,type, infobit
										false, null, null, 0, null, 0, 'commande'
						);

					} else {
						$tmpcmdeligncmdefourn= new CommandeFournisseurLigne($db);
						$tmpcmdeligncmdefourn->fetch($idlgnFourn);
						$result=$objectcf->updateline(
										$idlgnFourn,
										$tmpcmdeligncmdefourn->desc, 
										$tmpcmdeligncmdefourn->subprice, 
										$tmpcmdeligncmdefourn->qty + $lgnCmdeFourn[1], 
										$tmpcmdeligncmdefourn->remise_percent, 
										$tmpcmdeligncmdefourn->tva_tx, 
										$tmpcmdeligncmdefourn->localtax1_tx=0, 
										$tmpcmdeligncmdefourn->localtax2_tx=0, 
										'HT', 
										0, 0 
						);
					}
				}

				// on ajoute une ligne de détail restock associé à cette commande
				$newRestock->addLine($restockId, $idCmdFourn, 'order', $totalqty, 0);
			}
		}

		// on passe à la création des demande de prix
		if ($tblPropalFourn) {
			foreach ($tblPropalFourn as $propalFourn) {
				$idPropalFourn = 0;

				// si il on charge les demande de prix fournisseurs brouillons
				if ($conf->global->RESTOCK_FILL_PROPOSAL_DRAFT > 0) {
					// on vérifie qu'il n'y a pas une commande fournisseur déjà active
					$sql = 'SELECT rowid  FROM '.MAIN_DB_PREFIX.'supplier_proposal as sp';
					$sql.= ' WHERE fk_soc='.$propalFourn[0];
					$sql.= ' AND fk_statut=0';
					$sql.= ' AND entity='.$conf->entity;
					if  (	$conf->global->RESTOCK_FILL_PROPOSAL_DRAFT == 2
						||	$conf->global->RESTOCK_FILL_PROPOSAL_DRAFT == 4)
						$sql.= ' AND fk_user_author='.$user->id;

					$resql = $db->query($sql);
					if ($resql) {
						$objp = $db->fetch_object($resql);
						$idPropalFourn = $objp->rowid;
					}
					$objectsf = new SupplierProposal($db);
					$objectsf->fetch($idPropalFourn);
					// si il y en a un on ajoute un lien au restock
					$ret = $objectsf->add_object_linked("restock", $restockId);
				}

				// en création, si on a rien trouvé
				if ($idPropalFourn == 0) {
					$objectfournisseur = new Fournisseur($db);
					$objectfournisseur->fetch($propalFourn[0]);

					$objectsf = new SupplierProposal($db);
					//$objectcf->ref_supplier		= GETPOST("reforderfourn");
					$objectsf->socid			= $propalFourn[0];
					$objectsf->note_private		= '';
					$objectsf->note_public		= '';

					$objectsf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
					$objectsf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;
					// on fait lien après le create
					$objectsf->origin = "";
					$objectsf->origin_id = 0;

					$idPropalFourn = $objectsf->create($user);
					$ret = $objectsf->add_object_linked("restock", $restockId);

				}

				// V8 Bullchit utile sur les supplier order?
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;
				$totalqty=0;
				// ensuite on boucle sur les lignes de commandes
				foreach ($propalFourn[1] as $lgnPropalFourn) {
					$idlgnFourn = 0;
					// on vérifie qu'il n'y a pas déjà une ligne de commande pour ce produit
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as cofd';
					$sql.= ' WHERE fk_supplier_proposal='.$idPropalFourn;
					$sql.= ' AND fk_product='.$lgnPropalFourn[0];
					$resql = $db->query($sql);

					if ($resql) {
						$objp = $db->fetch_object($resql);
						$idlgnFourn = ($objp->rowid?$objp->rowid:0);
					}
					$totalqty+=$lgnPropalFourn[1];
					// si pas de ligne existante ou création d'une ligne à chaque fois
					if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_PROPOSAL_DRAFT <= 2) {
						// on cree la ligne de propale fournisseur
						$result=$objectsf->addline(
										'', 0,
										$lgnPropalFourn[1],	// $qty
										0, 0, 0,	// TxTVA, localtax1, localtax2
										$lgnPropalFourn[0]	// $fk_product
						);

					} else {
						// pour récupérer les anciennes valeur nécessaire à la mise à jour
						$tmpLignPropalFourn= new SupplierProposalLine($db);
						$tmpLignPropalFourn->fetch($idlgnFourn);
						$result=$objectsf->updateline(
										$idlgnFourn,
										$tmpLignPropalFourn->desc, 
										$tmpLignPropalFourn->subprice, 
										$tmpLignPropalFourn->qty + $lgnPropalFourn[1], 
										$tmpLignPropalFourn->remise_percent, 
										$tmpLignPropalFourn->tva_tx, 
										$tmpLignPropalFourn->localtax1_tx=0, 
										$tmpLignPropalFourn->localtax2_tx=0, 
										'HT', 
										0, 0 
						);
					}
				}

				// on ajoute une ligne de détail restock associé à cette propal
				$newRestock->addLine($restockId, $idPropalFourn, 'proposal', $totalqty, 0);

			}
		}

		// on se dirige vers le restock que l'on vient de créer
		header("Location: card.php?id=".$restockId);
		exit;

	} else // il y a eu un soucis à la création du restock
		var_dump($newRestock); 
}
llxFooter();
$db->close();
