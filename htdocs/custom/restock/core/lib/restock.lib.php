<?php
/* Copyright (C) 2014-2022	Charlene BENKE  <charlene@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *		\file	   /restock/lib/restock.lib.php
 *		\brief	  Ensemble de fonctions de base pour le module restock
 *	  \ingroup	restock
 */

function restock_admin_prepare_head()
{
	global $langs; //, $conf;
	$langs->load('restock@restock');

	$h = 0;
	$head = array();

	$head[$h][0] = "setup.php";
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'setup';
	$h++;
	$head[$h][0] = "restock.php";
	$head[$h][1] = $langs->trans("MoreSetup");
	$head[$h][2] = 'restock';
	$h++;
	$head[$h][0] = "restock_extrafields.php";
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;
	$head[$h][0] = "about.php";
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}

function restockPrepareHead($object)
{
	global $langs, $db, $conf;
	$langs->load('restock@restock');

	$h = 0;
	$head = array();

	$head[$h][0] = "card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';

	$h++;
	$object->fetchObjectLinked();
	$nbLinkedElement=0;
	foreach ($object->linkedObjects as $objecttype => $objects) {
		$nbLinkedElement+= count($objects);
	}
	$head[$h][0] = 'elementlink.php?id='.$object->id;
	$head[$h][1] = $langs->trans("links");
	if ($nbLinkedElement > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbLinkedElement.'</span>';
	$head[$h][2] = 'link';


	$h++;
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->restock->multidir_output[$object->entity ? $object->entity : $conf->entity].dol_sanitizeFileName($object->ref);

	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);

	$head[$h][0] = 'document.php?id='.$object->rowid;
	$head[$h][1] = $langs->trans("documents");
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';

	// Show more tabs from modules
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'restock');

	$h++;
	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
	}
	$head[$h][0] = 'note.php?id='.$object->rowid;
	$head[$h][1] = $langs->trans("note");
	if ($nbNote > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
	$head[$h][2] = 'notes';

	return $head;
}


function regroupRecursProduct($tblRestock, $lgncomponent) 
{
	global $db;
	$tblRestockNew= $tblRestock;

	$product_static = new Product($db);

	// si il y a des sous-composants
	if ($lgncomponent->nbcomposed > 0) {
		//Var_dump($lgncomponent->composedby);
		// on parcours les sous-composants
		foreach($lgncomponent->composedby as $currentcomponent) {

			$btrouve=false;
			$nbcomponent=0;
			foreach ($tblRestockNew as $lgncomponentpresent) {
				// si on a déjà référencé le produit on l'ajoute
				if ($currentcomponent[0] == $lgncomponentpresent->id) {
					// print 'updt'.$currentcomponent[0]."-".$currentcomponent[3].'('.$currentcomponent[1].')<br>';
					$tblRestockNew[$nbcomponent]->nbFactory+= $lgncomponent->nbFactory * $currentcomponent[1];
					$btrouve=true;
				}
				$nbcomponent++;
			}
			// si le produit n'est pas présent dans le tableau on le rajoute
			if (! $btrouve) {
				// print 'add'.$currentcomponent[0]."-".$currentcomponent[3].'('.$currentcomponent[1].')<br>';
				$RestockNew = new Restock($db);
				$product_static->fetch($currentcomponent[0]);
				$RestockNew->id = $currentcomponent[0];
				$RestockNew->ref = $product_static->ref;

				$RestockNew->libproduct = $currentcomponent[3];
				$RestockNew->nbFactory = $lgncomponent->nbFactory * $currentcomponent[1];
				$RestockNew->onBuyProduct = $product_static->status_buy;
				$ProductComponent= $RestockNew->getcomponent($currentcomponent[0], $currentcomponent[1]);
				
				$RestockNew->nbcomposed = count($ProductComponent);
				$RestockNew->composedby = $ProductComponent;

				$tblRestockNew[$nbcomponent] = $RestockNew;
				if (count($ProductComponent) >1) {
					foreach($ProductComponent as $recursivRestock)
						$tblRestockNew = regroupRecursProduct($tblRestockNew, $recursivRestock);
				}
			}
		}
	}
	return $tblRestockNew;
}

/**
 *	Return HTML code of the SELECT of list of all suppliers.
	*  This also set the number of contacts found into $this->num
	*
	*	@param  array|int	$selected   	Array of ID of pre-selected contact id
	*	@param  string		$htmlname  	    Name of HTML field ('none' for a not editable field)
	*	@param  int			$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit), 3=add an empty value only if more than one record into list
	*	@param  string		$exclude        List of contacts id to exclude
	*	@param	string		$limitto		Disable answers that are not id in this array list
	*	@param	integer		$showfunction   Add function into label
	*	@param	string		$moreclass		Add more class to class style
	*	@param	bool		$options_only	Return options only (for ajax treatment)
	* 	@param	int			$forcecombo		Force to use combo box (so no ajax beautify effect)
	*  @param	string		$moreparam		Add more parameters onto the select tag. For example 'style="width: 95%"' to avoid select2 component to go over parent container
	*  @param	string		$htmlid			Html id to use instead of htmlname
	*  @param	bool		$multiple		add [] in the name of element and add 'multiple' attribut
	*	@return	 int						<0 if KO, Nb of contact in list if OK
	*/
function selectsuppliers( $selected = '', $htmlname = 'supplierid', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $forcecombo = 0, $moreparam = '', $htmlid = '', $multiple = false)
{
	global $conf, $langs, $db;

	$langs->load('restock@restock');

	if (empty($htmlid)) $htmlid = $htmlname;

	if ($selected === '') $selected = array();
	elseif (!is_array($selected)) $selected = array($selected);
	$out = '';


	// seulement les fournisseurs actifs
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE fournisseur=1 AND status=1 ";

	dol_syslog("restock_lib::selectsuppliers", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($conf->use_javascript_ajax && !$forcecombo && !$options_only) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$out .= ajax_combobox($htmlid, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
		}

		if ($htmlname != 'none' && !$options_only) $out .= '<select class="flat'.($moreclass ? ' '.$moreclass : '').'" id="'.$htmlid.'" name="'.$htmlname.($multiple ? '[]' : '').'" '.($multiple ? 'multiple' : '').' '.(!empty($moreparam) ? $moreparam : '').'>';
		if (($showempty == 1 || ($showempty == 3 && $num > 1)) && !$multiple) $out .= '<option value="0"'.(in_array(0, $selected) ? ' selected' : '').'>&nbsp;</option>';
		if ($showempty == 2) $out .= '<option value="0"'.(in_array(0, $selected) ? ' selected' : '').'>-- '.$langs->trans("Internal").' --</option>';

		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($htmlname != 'none') {
					$disabled = 0;
					if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude)) $disabled = 1;
					if (is_array($limitto) && count($limitto) && !in_array($obj->rowid, $limitto)) $disabled = 1;
					$out .= '<option value="'.$obj->rowid.'"';
					if ($disabled) $out .= ' disabled';
					if (!empty($selected) && in_array($obj->rowid, $selected)) {
						$out .= ' selected';
					}
					$out .= '>';
					$out .= $obj->nom;
					$out .= '</option>';
				}
				else {
					if (in_array($obj->rowid, $selected)) {
						$out .= $obj->company;
					}
				}

				$i++;
			}
		} else {
			$out .= '<option value="-1"'.(($showempty == 2 || $multiple) ? '' : ' selected').' disabled>';
			$out .=  $langs->trans("NoSupplierDefinedAndActived");
			$out .= '</option>';
		}

		if ($htmlname != 'none' && !$options_only)
		{
			$out .= '</select>';
		}

		return $out;
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
}

/**
 * Return select list for categories (to use in form search selectors)
 *
 * @param  attary	$fournlist	fournish list
 * @param  string	$selected	Preselected value
 * @param  string	$htmlname	Name of combo list
 * @return string				Html combo list code
 */
function select_fournisseur($fournlist, $selected=0, $htmlname='search_fourn')
{
//	global $langs;

	// Print a select with each of them
	$moreforfilter ='<select class="flat" name="'.$htmlname.'">';
	$moreforfilter.='<option value="">&nbsp;</option>';	// Should use -1 to say nothing
	if (is_array($fournlist)) {
		foreach ($fournlist as $key => $value) {
			$moreforfilter.='<option value="'.$key.'"';
			if ($key == $selected) 
				$moreforfilter.=' selected="selected"';
			$moreforfilter.='>'.dol_trunc($value, 50, 'middle').'</option>';
		}
		$moreforfilter.='</select>';

		return $moreforfilter;
	}
}