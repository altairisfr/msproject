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
 *  \file	   htdocs/restock/restockFactory.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) 
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) 
	$res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/factory/core/lib/factory.lib.php');
dol_include_once('/restock/class/restock.class.php');
dol_include_once('/restock/core/lib/restock.lib.php');
dol_include_once('/factory/class/factory.class.php');

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';


require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
//if (! empty($conf->categorie->enabled))
//	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("factory@factory");
$langs->load("suppliers");


// Security check
$result = restrictedArea($user, 'factory');

$action=GETPOST("action");
$id = GETPOST('id', 'int');
$ref= GETPOST('ref', 'alpha');

$object = new Factory($db);
$product = new Product($db);
$entrepot = new Entrepot($db);

if (! $object->fetch($id, $ref) > 0)
	dol_print_error($db);


$id = $object->id;
$result = $product->fetch($object->fk_product);

$result = $entrepot->fetch($object->fk_entrepot);
$entrepotid= $object->fk_entrepot;

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"])) {
	$sref = "";
	$snom = "";
	$search_categ = 0;
	$search_fourn = 0;
	$selProposal =  "";
	$selOrder = "";
	$selFactory = "";
	$selOnlyWithQty = "";
	$filtered = "";
} else {
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


$restock_static=new Restock($db);

if ( isset($_POST['reload']) ) $action = 'restock';

// header forwarding issue
if ($action != "createrestock") {
	$title=$langs->trans("RestockFactoryOrderProduct");
	$helpurl='https://wiki.patas-monkey.com/index.php?title=ReStock';

	llxHeader('', $title, $helpurl, '');
	$head = factory_prepare_head($object, $user);
	dol_fiche_head($head, 'restock', $langs->trans("Factory"), -1, 'factory@factory');

	$linkback = '<a href="list.php?restore_lastsearch_values=1' . (! empty($productid) ? '&productid=' . $productid : '') . '">';
	$linkback.= $langs->trans("BackToList") . '</a>';

	$urllink=dol_buildpath('/factory/', 1).'list.php';
	// factory card
	$morehtmlref='<div class="refidno">';
	// Ref product
	$morehtmlref.='<br>'.$langs->trans('Product') . ' : ' . $product->getNomUrl(1)." - ".$product->label;
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK)) 
		$morehtmlref.=' (<a href="'.$urllink.'?productid='.$object->fk_product.'">'.$langs->trans("OtherFactory").'</a>)';

	// ref storage
	// rendre modifiable
	$morehtmlref.='<br><table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("Warehouse").' :</td>';
	$morehtmlref.='<td>';
		if ($object->fk_entrepot >0)
			$morehtmlref.=$entrepot->getNomUrl(1)." - ".$entrepot->lieu." (".$entrepot->zip.")" ;
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK)) 
		$morehtmlref.=' (<a href="'.$urllink.'?entrepotid='.$object->fk_entrepot.'">'.$langs->trans("OtherFactory").'</a>)';

	$morehtmlref.='</td></tr>';
	$morehtmlref.='</table>';
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="fichehalfleft">';
	//print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	print '<tr class="liste_titre nodrag nodrop"><th align=left colspan=2>';
	print $langs->trans("OFAdditionalInfos");
	print '</td></tr>';

	// Date start planned
	print '<tr><td width=20%>'.$langs->trans("FactoryDateStartPlanned");
	print '</td ><td width=20%>';
	print dol_print_date($object->date_start_planned, 'day');
	print ' - ';
	print dol_print_date($object->date_start_made, 'day');
	print '</td>';

	// Date end planned
	print '<tr><td >'.$langs->trans("FactoryDateEndPlanned").'</td>';
	print '<td >';
	print dol_print_date($object->date_end_planned, 'day');
	print ' - ';
	print dol_print_date($object->date_end_made, 'day');
	print '</td>';


	// Qty planned
	print '<tr><td>'.$langs->trans("QuantityPlanned").'</td>';
	print '<td >';
	print $object->qty_planned;
	print ' - ';
	print $object->qty_made;
	print '</td>';

	print '</table>';
	print '</div>';
	print '<div class="fichehalfright"><div class="ficheaddleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	print '<tr class="liste_titre nodrag nodrop"><th align=left colspan=2>';
	print $langs->trans("ProductsAdditionalInfos");
	print '</td></tr>';

	// TVA
	print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($product->tva_tx.($product->tva_npr?'*':''),true).'</td></tr>';


	// Status (to sell)
	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
	print $product->getLibStatut(2, 0);
	print "  ";
	print $product->getLibStatut(2, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
	$product->load_stock();
	print '<td>'.$product->stock_reel.'</td></tr>';

	print '</table>';
	print '</div>';

	print '</div></div>';
	print '<div style="clear:both"></div>';

}



$picto="cubes";
if ($action=="") {
	// premiere étape : la détermination des quantité à commander ou fabriquer
	print '<form action="restockFactory.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="filtered" value="1">';

	$restock_static=new Restock($db);
	$tblRestock=array();
	// on crée un tableau que pour le produit
	$tblRestock[0] = new Restock($db);
	$tblRestock[0]->id= $id;

	// on récupère les produits présents dans composition de L'OF
	$tblRestockTemp =$object->getChildsOF($id, "", "", $search_categ, $search_fourn);

	$nbcomponent=0;
	$product_static = new Product($db);

	foreach ($tblRestockTemp as $lgncomponent) {
		// on ajoute le composant trouvé au tableau des composants
		$tblRestock[$nbcomponent] = new Restock($db);

		$tblRestock[$nbcomponent]->id = $lgncomponent['id'];
		$product_static->fetch($lgncomponent['id']);

		$tblRestock[$nbcomponent]->ref_product = $lgncomponent['refproduct'];
		$tblRestock[$nbcomponent]->libproduct = $lgncomponent['label'];
		$tblRestock[$nbcomponent]->nbFactory = $lgncomponent['qtyplanned'];
		$tblRestock[$nbcomponent]->nbcomposed = count($lgncomponent['composed']);
		$tblRestock[$nbcomponent]->composedby = $lgncomponent['composed'];
		$tblRestock[$nbcomponent]->onBuyProduct = $product_static->status_buy;

		$nbcomponent++;
	}

	// petite boucle supplémentaire pour récupérer 
	// les sous composants présent dans composedby
	if (!empty($conf->global->RESTOCK_FACTORY_RECURSIVE_OF)) {
		$tblRestockTemp = $tblRestock;
		// on parcours le 
		foreach ($tblRestock as $lgncomponent) {
			$tblRestock = regroupRecursProduct($tblRestock, $lgncomponent);
		}
	}

	

	

	$tblRestock=$restock_static->enrichir_product($tblRestock);
	
	// $mesg=$langs->trans("InfoIncludeExcludeRecursivOF");
	if (!empty($conf->global->RESTOCK_FACTORY_RECURSIVE_OF)) 
		print load_fiche_titre($langs->trans("ProducttoOrderIncludeRecursivOF"), '', $picto);
	else {
		print load_fiche_titre($langs->trans("ProducttoOrderExcludeRecursivOF"), '', $picto);
		if ($user->admin)
			print "<b>".$langs->trans("IncludeExcludeRecursivOF")."<b><br><br>";
	}
	
	// Filter on categories
	$filterCateg = "";
	if (! empty($conf->categorie->enabled)) {
		$filterCateg.=$langs->trans('Categories').' : ';
		$filterCateg.=$htmlother->select_categories(0, $search_categ, 'search_categ', 1);
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

	print '</form>';
	print '<form action="restockFactory.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';
	print '<input type="hidden" name="id" value="'.$id.'">';


	// Lignes des titres
	print '<table class="border tableforfield" width="100%">';
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("NeededProduct").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="center">';

	//	if ($conf->global->RESTOCK_FACTORY_RECURSIVE_OF == 1)
	//		print $langs->trans("QtyRestockOrOF");
	//	else
	//		print $langs->trans("QtyRestock");
	print "</td></tr>\n";

	$idprodlist="";
	$product_static=new Product($db);
	foreach ($tblRestock as $lgnRestock) {
		$bDisplayLine=false;
		// si le prix est à l'achat ou est composé
		if ($lgnRestock->onBuyProduct == 1 || $lgnRestock->nbcomposed > 0) {
			$restockProductTypeSelect=!empty($conf->global->RESTOCK_PRODUCT_TYPE_SELECT)?$conf->global->RESTOCK_PRODUCT_TYPE_SELECT:0;
			// selon le paramétrage selon le type de produit/service
			if ($restockProductTypeSelect == 1 && $lgnRestock->fk_product_type=="0") 
				$bDisplayLine=true;
			if ($restockProductTypeSelect == 2 && $lgnRestock->fk_product_type=="1")
				$bDisplayLine=true;
			if ($restockProductTypeSelect == 0)
				$bDisplayLine=true;
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
			// on affiche le prix de vente de la commande
			print '<td align="right">'.price($lgnRestock->prixVenteCmdeHT).'</td>';
			print '<td align="right">'.price($lgnRestock->prixAchatHT).'</td>';
			print '<td align="right">'.$lgnRestock->nbFactory.'</td>';
			print '<td align="right">'.$lgnRestock->stockQty.'</td>';
			print '<td align="right">'.$lgnRestock->stockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");

			// détermination du besoin
			$estimedNeed=$lgnRestock->nbFactory;
			$restockReassortMode =!empty($conf->global->RESTOCK_REASSORT_MODE)?$conf->global->RESTOCK_REASSORT_MODE:0;
			// si on travail en réassort, on ne prend pas en compte le stock et les commandes en cours
			if ($restockReassortMode != 1 && $restockReassortMode != 3)
				$estimedNeed-= $lgnRestock->stockQty ;
				
			if ($restockReassortMode != 2 && $restockReassortMode != 3)
				$estimedNeed-= $lgnRestock->nbCmdFourn;
				
			// si il y a encore du besoin, (on a vidé toute le stock et les commandes)
			if ($restockReassortMode != 1 && $restockReassortMode != 3)
				if (($estimedNeed > 0) && ($lgnRestock->stockQtyAlert > 0))
					$estimedNeed+= $lgnRestock->stockQtyAlert;
					
			// si le besoin est négatif cela signifie que l'on a assez , 
			// pas besoin de commander
			if ($estimedNeed < 0)  
				$estimedNeed = 0;
			print '<td align="right" class="nowrap">';
			// seulement pour les produits
			//if ($product_static->type == 0) {
				// on indique que le produit n'a pas de fournisseur
				if (!empty($conf->supplier_proposal->enabled)) {
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
			print '<input type=hidden name="composed-'.$lgnRestock->id.'" value="'.$lgnRestock->nbcomposed.'">';
			print "</td></tr>\n";
		}
	}

	print '</table>';
	// pour mémoriser les produits à réapprovisionner
	// on vire le dernier '-' si la prodlist est alimenté
	if ($idprodlist)
		$idprodlist=substr($idprodlist, 0, -1);
	print '<input type=hidden name="prodlist" value="'.$idprodlist.'"></td>';	
	
	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<input title="'.$langs->trans("AllowSupplierProposal").'"type=checkbox name=chkproposal checked value=1>';
	print img_picto($langs->trans("AllowSupplierProposal"), "object_supplier_proposal");
	print '&nbsp;<input title="'.$langs->trans("AllowSupplierOrder").'" type=checkbox name=chkorder checked value=1>';
	print img_picto($langs->trans("AllowSupplierOrder"), "object_order");
	if (!empty($conf->factory->enabled)) {
		print '&nbsp;<input title="'.$langs->trans("AllowFactory").'" type=checkbox name=chkfactory checked value=1>';
		print img_picto($langs->trans('AllowFactory'), "object_factory@factory");
	}
	print '<br><br><input type="submit" class="butAction" name="bouton" value="'.$langs->trans('RestockOrder').'">';
	print '</div >';
	print '</form >';
} elseif ($action=="restock") {
	// deuxieme étape : la sélection des fournisseurs
	print '<form action="restockFactory.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';
	print '<input type="hidden" name="chkproposal" value="'.GETPOST("chkproposal").'">';
	print '<input type="hidden" name="chkorder" value="'.GETPOST("chkorder").'">';
	print '<input type="hidden" name="chkfactory" value="'.GETPOST("chkfactory").'">';

	print '<table class="liste" width="100%">';
	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("Qty").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("FournishSelectInfo").'</td>';
	print "</tr>\n";
	$product_static=new Product($db);
	
	$tblproduct=explode("-", GETPOST("prodlist"));	
	$isFactory=false;

	foreach ($tblproduct as $idproduct) {
		$nbprod=GETPOST("prd-".$idproduct);
		$nbComposed=(GETPOST("composed-".$idproduct)?GETPOST("composed-".$idproduct, "int"):0);
		// si on ne demande pas les of
		if ($nbComposed >0 && empty(GETPOST("chkfactory")))
			$nbProd=0;

		// on récupère les infos fournisseurs
		$product_fourn = new ProductFournisseur($db);
		$product_fourn_list = $product_fourn->list_product_fournisseur_price($idproduct, "", "");
		// si pas de fournisseur et demande de prix désactivé
		if (count($product_fourn_list) == 0 && $nbComposed == "" && empty(GETPOST("chkproposal"))) 
			$nbProd=0;
		
		// si fournisseur et commande fournisseur désactivé
		if (count($product_fourn_list) > 0 && $nbComposed == "" && empty(GETPOST("chkorder")) && empty(GETPOST("chkproposal"))) 
			$nbProd=0;
		$totHT=0;
		$totTTC=0;
		if ($nbprod > 0) {
			print "<tr >";
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
			print "<input type=text size=4 name='prd-".$idproduct."' value='".$nbprod."'>";
			print '<input type=hidden name="composed-'.$idproduct.'" value="'.$nbComposed.'">';
			print '</td><td width=60%>';
			
			//si il y a des demandes de prix activés
			if (!empty($conf->supplier_proposal->enabled) && GETPOST("chkproposal") &&  $nbComposed=="") {
				print img_picto($langs->trans("SupplierProposal"), "object_supplier_proposal");
				print "&nbsp;".$langs->trans("SupplierProposal")." ";
				print selectsuppliers( GETPOST('askpriceid-'.$idproduct, "array"), 'askpriceid-'.$idproduct,  0,  '',  '', 0, '',  false,  0, 'style="width: 60%"', '', true);
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
					if ($nbprod < $productfourn->fourn_qty) {
						// si on est or seuil de quantité on désactive le choix
						print '<input type=radio disabled title="'.$langs->trans("NotEnoughOrder").'">&nbsp;';
					} else {
						// on mémorise à la fois l'id du fournisseur et l'id du produit du fournisseur
						$fournField=$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id;
						// le taux de tva et le pourcentage de remise...
						$fournField.='-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent;
						if (count($product_fourn_list) > 1) {
							// on revient sur l'écran avec une préselection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $fournField) {
								$presel=true;
								$checked = " checked=true ";
							}
							print '<input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$fournField.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est sélectionné par défaut
							$presel=true;
							print '<input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$fournField.'">&nbsp;';
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
					print '</td>';	

					// Unit Charges ???
					if (! empty($conf->margin->enabled)) {
						if ($productfourn->fourn_unitcharges)
							$unitcharge = price($productfourn->fourn_unitcharges);
						elseif ($productfourn->fourn_qty)
							$unitcharge = price($productfourn->fourn_charges/$productfourn->fourn_qty);
						else
							$unitcharge = "&nbsp;";
					}
					if ($nbprod < $productfourn->fourn_qty)
						$nbprod = $productfourn->fourn_qty;
					$estimatedFournCost=$nbprod*$unitprice+($unitcharge != "&nbsp;" ? $unitcharge:0);
					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if ($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel==true) {
						$totHT+=  $estimatedFournCost;
						$totTTC+=  $estimatedFournCostTTC;
					}
					print '</tr>';
				}
				print "</table>";
			} else {
				// si on a juste les commandes fournisseurs
				if (!$conf->supplier_proposal->enabled && $nbComposed=="")
					print $langs->trans("NoFournishForThisProduct");

				// si il s'agit d'une fabrication
				if ($nbComposed>0) {
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
	print '<td width=100px align=right>';
	print price($totHT)." ".$langs->trans("Currency".$conf->currency);
	print '<br>'.price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';	
	print '</table>';
	print '</td></tr>';	
	print '</table>';

	/*
	 * Boutons actions
	*/
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';
	print '<tr><td >'.$langs->trans('ReferenceOfOrder').' :</td>';
	print '<td align=left>';
	// on mémorise la référence du de la facture client sur la commande fournisseur
	print '<input type=text size=30 name=reforderfourn value="'.$langs->trans('RestockofFactory').'&nbsp;'.$object->ref.'">';
	print '</td></tr></table>';
	print '</div>';
	//print '</div>';

	print '<div class="tabsAction">';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateRestock').'">';
	print '</div >';
	print '</form >';

} elseif ($action=="createrestock") {
	// derniére étape : la création des lignes
	// on récupère la liste des produits à commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utiliser un tableau pour stocker les commandes fournisseurs
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
			$numlines=count($tblCmdeFourn);
			$lineoffourn = -1;
			if (GETPOST("fourn-".$idproduct)) {
				$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
				if ($tblfourn[0]) {
					for ($j = 0 ; $j < $numlines ; $j++)
						if ($tblCmdeFourn[$j][0] == $tblfourn[0])
							$lineoffourn =$j;
			
					// si le fournisseur n'est pas déja dans le tableau des fournisseurs
					if ($lineoffourn == -1) {
						$tblCmdeFourn[$numlines][0] = $tblfourn[0];
						$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct),
										$tblfourn[1], $tblfourn[2], $tblfourn[3]
						));
					} else {
						$tblCmdeFourn[$lineoffourn][1] = array_merge(
										$tblCmdeFourn[$lineoffourn][1],
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
	$newRestock->origin_type = 'factory';
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
				$newRestock->addLine($restockId, $factoryId, 'factory', $factoryProductId[1], 0);
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
					// on interface cette commande fournisseur avec la commande client
					$ret = $objectcf->add_object_linked("factory", GETPOST("id"));
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
					$objectcf->origin_id 		= GETPOST("id");

					$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
					$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;

					$objectcf->origin = "factory";
					$objectcf->linked_objects[$objectcf->origin] = $objectcf->origin_id;
					$idCmdFourn = $objectcf->create($user);
				}
				$ret = $objectcf->add_object_linked("restock", $restockId);

				// V8 Bullchit
				$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;
				$totqty = 0;
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

					$totqty+=$lgnCmdeFourn[1];
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

				// on ajoute une ligne de détail restock associé à cette propal
				$newRestock->addLine($restockId, $idCmdFourn, 'order', $totqty, 0);

			}
		}

		// on passe à la création des demande de prix
		if ($tblPropalFourn) {
			foreach ($tblPropalFourn as $propalFourn) {
				$totqty=0;
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

				// ensuite on boucle sur les lignes de commandes
				foreach ($propalFourn[1] as $lgnPropalFourn) {
					$idlgnFourn = 0;
					$totqty+=$lgnPropalFourn[1];
					// on vérifie qu'il n'y a pas déjà une ligne de commande pour ce produit
					$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as cofd';
					$sql.= ' WHERE fk_supplier_proposal='.$idPropalFourn;
					$sql.= ' AND fk_product='.$lgnPropalFourn[0];
					$resql = $db->query($sql);

					if ($resql) {
						$objp = $db->fetch_object($resql);
						$idlgnFourn = ($objp->rowid?$objp->rowid:0);
					}

					// si pas de ligne existante ou création d'une ligne à chaque fois
					if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
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
				$newRestock->addLine($restockId, $idPropalFourn, 'proposal', $totqty, 0);
			}
		}

		// on se dirige vers le restock que l'on vient de créer
		header("Location: ../card.php?id=".$restockId);
		exit;

	} else // il y a eu un soucis à la création du restock
		var_dump($newRestock);

}

print '</div>';
print '<div class="fichecenter"><div class="fichehalfleft">';
print '</div></div>';

llxFooter();
$db->close();