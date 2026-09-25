<?php
/* Copyright (C) 2013-2022	Charlene BENKE		<charlene@patas-monkey.com>
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
 *  \file	   htdocs/restock/restockSupplier.php
 *  \ingroup	stock
 *  \brief	  Page to manage reodering
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) 
	$res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) 
	$res=@include("../../../main.inc.php");	// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('restock/class/restock.class.php');
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/projet/class/project.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->load("companies");


$action=GETPOST("action");
$id = GETPOST('id', 'int');
if ($id == 0)
	$id = GETPOST('socid', 'int');

// Security check
if ($user->socid) $socid=$user->socid;
$result=restrictedArea($user, 'societe', $id, '');
//$result=restrictedArea($user,'produit','','','','','','');


$ref=GETPOST('ref', 'alpha');
$object = new Fournisseur($db);

$search_categ=GETPOST('search_categ', 'alpha');

if (! $object->fetch($id, $ref) > 0)
	dol_print_error($db);

$object->fetch_thirdparty();
$id = $object->id;

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"])) {
	$search_categ=0;
}


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);


$restock_static=new Restock($db);

if ( isset($_POST['reload']) ) $action = 'restock';

// header forwarding issue
// en cas de createrestock, comme il y a redirection ensuite, on n'affiche pas la page
if ($action!="createrestock") {
	$title = $langs->trans("ThirdParty").' - '.$langs->trans("RestockOrderProduct");
	if (!empty($conf->global->MAIN_HTML_TITLE) 
		&& preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) 
		&& $object->name
	) 
		$title = $object->name.' - '.$langs->trans("RestockOrderProduct");

	$help_url='https://wiki.patas-monkey.com/index.php?title=reStock';

	llxHeader('', $title, $help_url, '');
	$head = societe_prepare_head($object);

	dol_fiche_head($head, 'restock', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php">'.$langs->trans("BackToList").'</a>';

	if (! empty($conf->notification->enabled)) $langs->load("mails");

	dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

	$cssclass='titlefield';
	
	print '<div class="fichecenter">';
	
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	if (! empty($conf->global->SOCIETE_USEPREFIX)) {
		// Old not used prefix field
		print '<tr><td class="'.$cssclass.'">'.$langs->trans('Prefix').'</td>';
		print '<td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client) {
		print '<tr><td class="'.$cssclass.'">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) 
			print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr><td class="'.$cssclass.'">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) 
			print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	print "</table>";
	print '</div>';

}
if ($action=="") {

	if (! empty($conf->categorie->enabled)) {

		// premiere �tape : la d�termination des quantit� � commander 
		print '<form action="restockSupplier.php" method="post" name="formulaire">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		print '<input type="hidden" name="action" value="">';
		print '<input type="hidden" name="id" value="'.$id.'">';


		// Filter on categories
	 	$filtercateg=$langs->trans('Categories'). ': ';
		$filtercateg.=$htmlother->select_categories(0, $search_categ, 'search_categ', 1);

		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" >'.$filtercateg.'</td>';

		print '<td colspan=4 align=right>';
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
	}


	$tblRestock=array();

	// on r�cup�re les produits pr�sents dans la commande
	$tblRestock=$restock_static->get_array_product_fourn($tblRestock, $id, $search_categ);

	// on g�re la d�composition des produits
	$tblRestockTemp=array();

	foreach ($tblRestock as $lgnRestock) {
		// on r�cup�re la composition et les quantit�s
		$tbllistofcomponent=$restock_static->getcomponent($lgnRestock->id, 1);

		foreach ($tbllistofcomponent as $lgncomponent) {
			$numlines=count($tblRestockTemp);
			$lineofproduct = -1;
			// on regarde si on trouve d�j� le produit dans le tableau 
			for ($j = 0 ; $j <= $numlines ; $j++)
				if ($tblRestockTemp[$j]->id == $lgncomponent[0])
					$lineofproduct=$j;

			// si produit d�ja r�f�renc�, on ajoute au tableau en multipliant par la quantit� du composant
			if ($lineofproduct == -1) {
				// on rajoute une ligne dans le tableau
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$numlines++;
			}
		}
	}

	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);

	// Lignes des titres
	print '<br>';

	// premiere �tape : la d�termination des quantit� � commander 
	print '<form action="restockSupplier.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<input type="hidden" name="action" value="restock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	
	print '<table class="liste" width="100%">';
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	//	print '<td class="liste_titre" align="right">'.$langs->trans("Ordered").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockDesired").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";

	// on cr�e la liste des choses � cr�er
	$idprodlist="";
	$cmdedetlist="";
	
	$product_static=new Product($db);
	foreach ($tblRestock as $lgnRestock) {
		if (($lgnRestock->onBuyProduct == 1 ) 
		&& ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==1 && $lgnRestock->fk_product_type=="0" 
		|| $conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==2 && $lgnRestock->fk_product_type=="1"
		|| $conf->global->RESTOCK_PRODUCT_TYPE_SELECT ==0)) {

			$var=!$var;
			print "<tr ".$bc[$var].">";
			$idprodlist.=$lgnRestock->id."-";

			print '<td class="nowrap">';
			$product_static->id = $lgnRestock->id;
			$product_static->ref = $lgnRestock->ref_product;
			$product_static->type = 0;
			print $product_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			// on affiche le prix de vente de la commande
			print '<td align="right">'.price($lgnRestock->prixVenteCmdeHT).'</td>';
			print '<td align="right">'.price($lgnRestock->prixAchatHT).'</td>';
			//print '<td align="right">'.$lgnRestock->nbCmdeClient.'</td>';
			print '<td align="right">'.$lgnRestock->stockQty.'</td>';
			print '<td align="right">'.$lgnRestock->stockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->stockQtyDesired.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
			if (count($product_fourn_list) > 0) {
				// d�termination du besoin
				$estimedNeed=0;
				// si on travail en r�assort, on ne prend pas en compte le stock et les commandes en cours
				if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->stockQty ;
					
				if ($conf->global->RESTOCK_REASSORT_MODE != 2 && $conf->global->RESTOCK_REASSORT_MODE != 3)
					$estimedNeed-= $lgnRestock->nbCmdFourn;
					
				// si il y a encore du besoin, (on a vid� toute le stock et les commandes)
				if ($conf->global->RESTOCK_REASSORT_MODE != 1 && $conf->global->RESTOCK_REASSORT_MODE != 3)
						$estimedNeed+= $lgnRestock->stockQtyAlert;
				
				if ($estimedNeed < 0)  // si le besoin est n�gatif cela signifie que l'on a assez , pas besoin de commander
					$estimedNeed = 0;

				print '<td align="right">';
				print '<input type=text size=5 name="prd-'.$lgnRestock->id.'" value="'.round($estimedNeed).'"></td>';
			} else {
				print '<td align="right">';
				print $langs->trans("NoFournish");
				print '</td>';
			}
			print "</tr>\n";
		}
	}

	print '</table>';
	// pour m�moriser les produits � r�stockvisionner
	// on vire le dernier '-' si la prodlist est aliment�
	if ($idprodlist)
		$idprodlist=substr($idprodlist, 0, -1);
	print '<input type=hidden name="prodlist" value="'.$idprodlist.'"></td>';	

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction"><br><center>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('RestockOrder').'">';
	print '</center></div >';

	print '</form >';
} elseif ($action=="restock") {
	// deuxieme �tape : la s�lection des fournisseur
	print '<form action="restockSupplier.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';
	print '<input type="hidden" name="cmdedetlist" value="'.GETPOST("cmdedetlist").'">';

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
	$var=true;
	foreach ($tblproduct as $idproduct) {
		$nbprod=GETPOST("prd-".$idproduct);
		if ($nbprod > 0) {
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$product_static->id = $idproduct;
			$product_static->fetch($idproduct);
			print $product_static->getNomUrl(1, '', 24);
			print '</td>';
			print '<td>'.$product_static->label.'</td>';
			print '<td align=center>';
			print "<input type=text size=4 name='prd-".$idproduct."' value='".$nbprod."'>";
			print '</td><td width=60%>';
			
			// on r�cup�re les infos fournisseurs
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($idproduct, "", "");

			if (count($product_fourn_list) > 0) {
				print '<table class="liste" width="100%">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans("Suppliers").'</td>';
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
					//var_dump($productfourn);
					print "<tr >";
					$presel=false;
					if ($nbprod < $productfourn->fourn_qty) {
						// si on est or seuil de quantit� on d�sactive le choix
						print '<td>'.img_picto('disabled', 'disable') ;
					} else {
						$fournField=$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'-'.$productfourn->fourn_remise_percent;
						// on m�morise � la fois l'id du fournisseur et l'id du produit du fournisseur
						if (count($product_fourn_list) > 1) {
							// on revient sur l'�cran avec une pr�selection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $fournField) {	
								$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$fournField.'">&nbsp;';
						} else {
							// si il n'y a qu'un fournisseur il est s�lectionn� par d�faut
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$fournField.'">&nbsp;';
						}
						//mouchard pour les tests
						//print '<input type=text  value="'.$fournField.'">&nbsp;';
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
							$unitcharge=price($productfourn->fourn_unitcharges); 
						elseif ($productfourn->fourn_qty)
							$unitcharge=price($productfourn->fourn_charges/$productfourn->fourn_qty);
						else
							$unitcharge="&nbsp;";
					}
					if ($nbprod < $productfourn->fourn_qty)
						$nbprod = $productfourn->fourn_qty;
					$estimatedFournCost=$nbprod*$unitprice+($unitcharge != "&nbsp;" ? $unitcharge:0);
					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if ($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel==true) {
						$totHT = $totHT+$estimatedFournCost;
						$totTTC = $totTTC+$estimatedFournCostTTC;
					}
					print '</tr>';
				}
				print "</table>";
			} else
				print $langs->trans("NoFournishForThisProduct");

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
	print price($totHT)." ".$langs->trans("Currency".$conf->currency).'<br>';
	print price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';	
	print '</table>';
	print '</td></tr>';	
	print '</table>';

	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<table width=75%><tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td>';
	print '<td align=left>';
	// on m�morise la r�f�rence du de la facture client sur la commande fournisseur
	$reforderfourn = $langs->trans('RestockofProduct').'&nbsp;'.$object->nom ;
	print '<input type=text size=40 name=reforderfourn value="'.$reforderfourn.'">';
	print '</td>';
	print '<td align=right>';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'"></td>';
	print '</tr></table>';
	print '</div >';
	print '</form >';
} elseif ($action=="createrestock") {
	// derni�re �tape : la cr�ation des commandes fournisseurs
	// on r�cup�re la liste des produits � commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utiliser un tableau pour stocker les commandes fournisseurs
	$tblCmdeFourn=array();
	// on parcourt les produits pour r�cup�rer les fournisseurs, les produits et les quantit�s
	foreach ($tblproduct as $idproduct) {
		$numlines=count($tblCmdeFourn);
		$lineoffourn = -1;
		if (GETPOST("fourn-".$idproduct)) {
			$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
			if ($tblfourn[0]) {
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblCmdeFourn[$j][0] == $tblfourn[0])
						$lineoffourn =$j;
	
				// si le fournisseur n'est pas d�ja dans le tableau des fournisseurs
				if ($lineoffourn == -1) {
					$tblCmdeFourn[$numlines][0] = $tblfourn[0];
					$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct),
									$tblfourn[1], $tblfourn[2], $tblfourn[3]));
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

	// V8 Bullchit
	$conf->global->SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY = 1;

	// on va maintenant cr�er les commandes fournisseurs
	foreach ($tblCmdeFourn as $cmdeFourn) {
		$idCmdFourn = 0;
		// si il on charge les commandes fournisseurs brouillons
		if ($conf->global->RESTOCK_FILL_ORDER_DRAFT > 0) {
			// on v�rifie qu'il n'y a pas une commande fournisseur d�j� active
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
			$objectcf->origin = "commande";
			$objectcf->origin_id = GETPOST("id");
			$objectcf->fetch($idCmdFourn);
			// on ajoute le lien
			$ret = $objectcf->add_object_linked();
		}

		// en création
		if ($idCmdFourn == 0) {
			$objectfournisseur = new Fournisseur($db);
			$objectfournisseur->fetch($cmdeFourn[0]);

			$objectcf = new CommandeFournisseur($db);
			$objectcf->ref_supplier  	= GETPOST("reforderfourn");
			$objectcf->socid		 	= $cmdeFourn[0];
			$objectcf->note_private	= '';
			$objectcf->note_public   	= '';
			$objectcf->origin_id = GETPOST("id");

			$objectcf->cond_reglement_id =$objectfournisseur->cond_reglement_supplier_id;
			$objectcf->mode_reglement_id =$objectfournisseur->mode_reglement_supplier_id;

			$objectcf->origin = "commande";
			$objectcf->linked_objects[$objectcf->origin] = $objectcf->origin_id;
			$idCmdFourn = $objectcf->create($user);
		}

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

			// si pas de ligne existante ou création d'une ligne pour chaque commande
			if ($idlgnFourn == 0 || $conf->global->RESTOCK_FILL_ORDER_DRAFT <= 2) {
				// on cree la commande fournisseur
				$result=$objectcf->addline(
								'', 0, 				// desc, unit price
								$lgnCmdeFourn[1],	// $qty
								$lgnCmdeFourn[3],	// TxTVA
								0, 0,				// locatax 1 & 2
								$lgnCmdeFourn[0],	// $fk_product
								$lgnCmdeFourn[2],	// $fk_prod_fourn_price
								0, 					// $fourn_ref
								$lgnCmdeFourn[4],	// $remise_percent
				);
				// not needed
				//				'HT',				// $price_base_type
				//				0, 0, 0,			// pu_ttc ,type, infobit
				//				false, null, null, 0, null, 0, ''	// notrigger,  date_start, date_end, array_options, fk_unit, pu_ht_devise, origin
				//);

				// récup de l'id de la que l'on vient de créer
				$sql = 'SELECT rowid from '.MAIN_DB_PREFIX.'commande_fournisseurdet';
				$sql.= ' WHERE fk_commande = '.$idCmdFourn;
				$sql.= ' ORDER BY rowid desc';
				$resql = $db->query($sql);
	
				if ($resql) {
					$objcf = $db->fetch_object($resql);
					$idlgnFourn = $objcf->rowid;
				}
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
								'HT', 0, 0
				);
			}

			// on enregistre l'id pour la ligne de la commande client
			// attention, si le produit est sur deux ligne dans la commande client cela d�conne
			
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commandedet';
			$sql.= ' SET fk_commandefourndet = '.$idlgnFourn;
			$sql.= ' WHERE fk_product = '.$lgnCmdeFourn[0];
			$sql.= ' AND fk_commande = '. $id;
			$resqlupdate = $db->query($sql);
		}
	}
	
	
	// une fois que c'est termin�, on affiche la ou les commandes fournisseurs cr�e
	if (count($tblCmdeFourn) == 1) {
		header("Location: ".DOL_URL_ROOT."/fourn/commande/card.php?id=".$idCmdFourn);
	} else {
		// on cr�e les commandes et on les listes sur l'�cran
		header("Location: ".DOL_URL_ROOT."/fourn/commande/list.php?search_refsupp=".GETPOST("reforderfourn"));
	}
	exit;
}

print '</div>';
print '<div class="fichecenter"><div class="fichehalfleft">';
/*
 * Linked object block
*/
if ((int) DOL_VERSION >= 5)
	$somethingshown = $form->showLinkedObjectBlock($object, "");
else
	$somethingshown=$object->showLinkedObjectBlock();

print '</div>';
print '</div>';

llxFooter();
$db->close();