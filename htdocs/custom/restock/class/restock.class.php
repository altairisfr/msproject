<?php
/* Copyright (C) 2013-2023	Charlene BENKE		<charlene@patas-monkey.com>
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
 *	\file	   htdocs/restock/class/restock.class.php
 *	\ingroup	categorie
 *	\brief	  File of class to restock
 */

/**
 *	Class to manage Restock
 */
class Restock  extends CommonObject
{
	var $id;
	var $ref_product;
	var $libproduct;
	var $prixAchatHT;			// upercase
	var $prixVenteHT;			// upercase
	var $prixVenteCmdeHT;		// pour les commandes clients
	var $composedProduct;		// le produit est fabricable
	var $onBuyProduct;			// upercase
	var $stockQty=0;			// upercase
	var $nbBillDraft=0;
	var $nbBillValidate=0;
	var $nbBillpartial=0;
	var $nbBillpaye=0;
	var $nbCmdeDraft=0;
	var $nbCmdeValidate=0;
	var $nbCmdePartial=0;
	var $nbCmdeClient=0;
	var $MntCmdeClient=0;		// upercase
	var $nbPropDraft=0;
	var $nbPropValidate=0;
	var $nbPropSigned=0;
	var $nbCmdFourn=0;
	var $nbtoStockSupplier=0;

	// nouveau champs pour les commandes lines
	var $fk_commandedet;
	var $fk_product;
	var $fk_project;
	var $fk_commande;
	var $date_commande;
	var $qty;


	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'restock';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'restock';
	/**
	 * @var int  Does restock support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for restock. Must be the part after the 'object_' into object_restock.png
	 */
	public $picto = 'restock@restock';


	const STATUS_DRAFT = 0; // état brouillon, en cours de préparation
	const STATUS_VALIDATE = 1; // état attente, le ou les mouvements ne sont pas encore réalisés
	const STATUS_CLOSE = 2; // état terminé, plus de mouvement à faire
	const STATUS_CANCELED = 9; //  le mouvement n'aura pas lieu finalement

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'visible'=>-1, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'visible'=>4, 'noteditable'=>'1', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'position'=>30, 'notnull'=>0, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth200', 'showoncombobox'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'position'=>31, 'notnull'=>1, 'noteditable'=>1,'visible'=>1,),

		'nborder' => array('type'=>'integer', 'label'=>'NbOrder', 'enabled'=>'$conf->fournisseur->enabled', 'position'=>41, 'notnull'=>0, 'visible'=>2,),
		'nbproposal' => array('type'=>'integer', 'label'=>'NbProposal', 'enabled'=>'$conf->supplier_proposal->enabled', 'position'=>42, 'notnull'=>0, 'visible'=>2,),
		'nbof' => array('type'=>'integer', 'label'=>'NbOf', 'enabled'=>'$conf->factory->enabled', 'position'=>43, 'notnull'=>0, 'visible'=>2,),
		'fk_originid' => array('type'=>'integer', 'label'=>'OriginId', 'enabled'=>1, 'position'=>44, 'notnull'=>0, 'visible'=>-2,),
		'origin_type' => array('type'=>'varchar(25)', 'label'=>'OriginType', 'enabled'=>1, 'position'=>45, 'notnull'=>-1, 'visible'=>-1,'noteditable'=>1,'arrayofkeyval'=>array('factory'=>'Factory', 'order'=>'Order', 'product'=>'Product')),
		'fk_project' =>array('type'=>'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label'=>'Project', 'enabled'=>1, 'visible'=>-1, 'position'=>25),

		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>1, 'position'=>200, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'noteditable'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Valid&eacute;', '2'=>'Termin&eacute;', '9'=>'Annul&eacute;'),),

		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'position'=>60, 'notnull'=>0, 'visible'=>3,),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>1, 'position'=>61, 'notnull'=>0, 'visible'=>-2,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'position'=>62, 'notnull'=>0, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>0, 'position'=>1010, 'notnull'=>-1, 'visible'=>-1,),

	);

	public $rowid;
	public $ref;
	public $label;
	public $nborder;
	public $nbproposal;
	public $nbof;
	public $sumQtyOrder;
	public $sumQtyProposal;
	public $sumQtyOf;


	public $origin_type;
	public $fk_originid;
	public $elementOrigin;	// l'object à l'origin du restock

	public $description;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $model_pdf;
	public $status;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	public $table_element_line = 'restockdet';

	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_restock';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'restockdet';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables=array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade=array('restockdet');

	/**
	 * @var restockLine[]     Array of subtable lines
	 */
	public $lines = array();

	public $arrayStatut = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->arrayStatut[self::STATUS_DRAFT] = 'Draft';
		$this->arrayStatut[self::STATUS_VALIDATE] = 'Validate';
		$this->arrayStatut[self::STATUS_CLOSE] = 'Closed';
		$this->arrayStatut[self::STATUS_CANCELED] = 'Disabled';

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) 
			$this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) 
			$this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2]=$langs->trans($val2);
					}
				}
			}
		}
	}

	function get_array_product_fourn($tblRestock, $idfourn, $search_categ=0)
	{
		global $conf;
		// on récupère les stock sur les produits 
		$sql = 'SELECT DISTINCT pfp.fk_product';
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= ", ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON pfp.fk_product = cp.fk_product"; 
		$sql.= " WHERE p.rowid = pfp.fk_product";
		$sql.= " and pfp.fk_soc =".$idfourn;
		$sql.= " AND p.tobuy = 1";

		if (!empty($conf->global->RESTOCK_PRODUCT_TYPE_SELECT))
			switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
				case  1 :	// seulement product
					$sql.= " AND p.fk_product_type =0";
					break;
				case 2 :	// seulement service
					$sql.= " AND p.fk_product_type =1";
					break;
			}

		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY pfp.fk_product";
		dol_syslog(get_class($this)."::get_array_product_fourn sql=".$sql);
		// print $sql." // ".$conf->global->RESTOCK_PRODUCT_TYPE_SELECT."<br>";
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déjà dans le tableau des produits
				if ($lineofproduct == -1) {
					// on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($this->db);
					$tblRestock[$numlines]->id= $objp->fk_product;
				}
				$i++;
			}
		} else
			var_dump($this->db);
		
		return $tblRestock;
	}

	function get_array_product_cmde($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co ON co.rowid = cod.fk_commande";
		if ($search_fourn > 0)
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product"; 
		$sql.= " WHERE co.entity = ".$conf->entity;
		$sql.= " AND cod.fk_product > 0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND cod.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND cod.product_type =1";
				break;
		}

		$sql.= " AND co.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde sql=".$sql);
		// print $sql."//".$conf->global->RESTOCK_PRODUCT_TYPE_SELECT."<br>";
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product) {
						$lineofproduct=$j;
						break;
					}

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jour les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbCmdeDraft+= $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbCmdeValidate+= $objp->nbCmde;
					else {
						$tblRestock[$lineofproduct]->nbCmdePartial+= $objp->nbCmde;
					}
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($this->db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbCmdeDraft = $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbCmdeValidate = $objp->nbCmde;
					else {
						$tblRestock[$numlines]->nbCmdePartial = $objp->nbCmde;
					}
				}
				$i++;
			}
		}
		// si commande en cours, on récupère les qte déjà expédiées
		if ($statut==2 && $conf->global->RESTOCK_ORDER_RUNNING_SUBSTRACT) {
			$sql = 'SELECT DISTINCT cod.fk_product, sum(ed.qty) as nbCmdeSend';
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co ON co.rowid = cod.fk_commande";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON ed.fk_origin_line = cod.rowid";
			if ($search_fourn > 0)
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
			// We'll need this table joined to the select in order to filter by categ
			if ($search_categ > 0)
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product"; 
			$sql.= " WHERE co.entity = ".$conf->entity;
			$sql.= " AND cod.fk_product > 0";
			switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
				case  1 :	// seulement product
					$sql.= " AND cod.product_type =0";
					break;
				case 2 :	// seulement service
					$sql.= " AND cod.product_type =1";
					break;
			}
			$sql.= " AND co.fk_statut =2";

			if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
			if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
			if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
			$sql.= " GROUP BY cod.fk_product";
			dol_syslog(get_class($this)."::get_array_product_cmde sql=".$sql);
			// print $sql."//".$conf->global->RESTOCK_PRODUCT_TYPE_SELECT."<br>";
			$resql = $this->db->query($sql);
			if ($resql) {
				$i=0;
				$num = $this->db->num_rows($resql);
	
				while ($i < $num) {
					$numlines=count($tblRestock);
					$lineofproduct = -1;
					$objp = $this->db->fetch_object($resql);
					// on regarde si on trouve déjà le produit dans le tableau 
					for ($j = 0 ; $j < $numlines ; $j++)
						if ($tblRestock[$j]->id == $objp->fk_product) {
							$lineofproduct=$j;
							break;
						}
	
					$tblRestock[$lineofproduct]->nbCmdePartial-= $objp->nbCmdeSend;
					$i++;
				}
			}
		}

		return $tblRestock;
	}

	function get_array_product_bill($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT fad.fk_product, sum(fad.qty) as nbBill';
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fad";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON fa.rowid = fad.fk_facture";
		if ($search_fourn > 0)
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON fad.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON fad.fk_product = cp.fk_product"; 
		$sql.= " WHERE fa.entity = ".$conf->entity;
		$sql.= " AND fad.fk_product >0";
		if ($statut !=4)
			$sql.= " AND fa.fk_statut =".$statut;
		else
			$sql.= " AND fa.paye = 1 ";

		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND fad.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND fad.product_type =1";
				break;
		}

		if ($month > 0) {
			if ($year > 0 ) {
				$sql.= " AND fa.datef BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."'";
				$sql.= " AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			} else
				$sql.= " AND date_format(fa.datef, '%m') = '".$month."'";
		} elseif ($year > 0) {
			$sql.= " AND fa.datef BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."'";
			$sql.= " AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		}

		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY fad.fk_product";
		dol_syslog(get_class($this)."::get_array_product_bill sql=".$sql);


		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {	
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;
				// si le produit est déjà dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jours les données 
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbBillDraft+= $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbBillValidate+= $objp->nbBill;
					elseif ($statut==3)
						$tblRestock[$lineofproduct]->nbBillpartial+= $objp->nbBill;
					else
						$tblRestock[$lineofproduct]->nbBillpaye+= $objp->nbBill;
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($this->db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbBillDraft = $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbBillValidate = $objp->nbBill;
					elseif ($statut==3)
						$tblRestock[$numlines]->nbBillpartial = $objp->nbBill;
					else
						$tblRestock[$numlines]->nbBillpaye = $objp->nbBill;
				}
				$i++;
			}
		}

		return $tblRestock;
	}
	
	function get_array_product_cmde_client($tblRestock, $rowid,  $search_categ="", $search_fourn="")
	{
		global $conf;
		
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde, sum(total_ht) as MntCmde, count(*) as nblgn';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		if ($search_fourn > 0)
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product"; 
		$sql.= " WHERE cod.fk_commande=".$rowid;
		$sql.= " AND cod.fk_product > 0";
		if (!empty($conf->global->RESTOCK_PRODUCT_TYPE_SELECT)) {
			switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
				case  1 :	// seulement product
					$sql.= " AND cod.product_type =0";
					break;
				case 2 :	// seulement service
					$sql.= " AND cod.product_type =1";
					break;
			}
		}
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;

		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);
		//print $sql."<br>";
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {	
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);

				// si il n'y a qu'une ligne associé au produit on mémorise la ligne de détail pour faire le lien
				if ($objp->nblgn == 1) {
					$sql = 'SELECT DISTINCT cod.rowid';
					$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
					$sql.= " WHERE cod.fk_commande=".$rowid;
					$sql.= " AND cod.fk_product=".$objp->fk_product;
					dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);

					$resqldet = $this->db->query($sql);
					if ($resqldet) {
						$objcdet = $this->db->fetch_object($resqldet);
						$fk_commandedet = $objcdet->rowid;
					}
				} else
					$fk_commandedet = 0;

				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					$tblRestock[$lineofproduct]->nbCmdeClient+= $objp->nbCmde;
					$tblRestock[$lineofproduct]->MntCmdeClient+= $objp->MntCmde;
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($this->db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					$tblRestock[$numlines]->nbCmdeClient = $objp->nbCmde;
					$tblRestock[$numlines]->MntCmdeClient = $objp->MntCmde;
					$tblRestock[$numlines]->fk_commandedet = $fk_commandedet;
				}
				$i++;
			}
		}
		return $tblRestock;
	}
	
	function only_one_line_order_product_det($commandeid, $productid)
	{
		$fk_commandedet = 0;

		$sql = 'SELECT count(*) as nblgn';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " WHERE cod.fk_commande=".$commandeid;
		$sql.= " AND cod.fk_product=".$productid;
		$resqldet = $this->db->query($sql);
		if ($resqldet) {
			$objc = $this->db->fetch_object($resqldet);
			$nblgn=$objc->nblgn;
		}
		// si il n'y a qu'une ligne associé au produit on mémorise la ligne de détail pour faire le lien
		if ($nblgn == 1) {
			$sql = 'SELECT DISTINCT cod.rowid';
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
			$sql.= " WHERE cod.fk_commande=".$commandeid;
			$sql.= " AND cod.fk_product=".$productid;
			dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);

			$resqldet = $this->db->query($sql);
			if ($resqldet) {
				$objcdet = $this->db->fetch_object($resqldet);
				$fk_commandedet = $objcdet->rowid;
			}
		}
		return $fk_commandedet;
	}
	
	// mise à jour du prix de vente fournisseur à partir du prix de vente du produit sur la commande
	function update_product_price_cmde_client($rowid, $idproduct)
	{
		global $conf;
		
		$sql = 'SELECT DISTINCT price';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " where cod.fk_commande=".$rowid;
		$sql.= " and cod.fk_product=".$idproduct;


		dol_syslog(get_class($this)."::update_product_price_cmde_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$productprice=$objp->price;
			// on pondère
			$coef=$conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN/100;
			$productprice=$productprice * $coef;
			// on met à jour le prix fournisseur
			$sql= "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price ";
			$sql.= " SET price=".$productprice;
			$sql.= " , unitprice=".$productprice;
			$sql.= " where fk_product=".$idproduct;
			$resqlupdate = $this->db->query($sql);
			return 1;
		}
		return 0;
	}

	// mise à jour du prix de vente fournisseur à partir du prix de vente du produit sur la commande
	function add_contact_delivery_client($cmdeClientid, $cmdeFournId)
	{
		global $conf;
		
		$sql='select fk_socpeople from '.MAIN_DB_PREFIX.'element_contact';
		$sql.=" where fk_c_type_contact=102 and element_id=".$cmdeClientid;

		dol_syslog(get_class($this)."::add_contact_delivery_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$fk_socpeople=$objp->fk_socpeople;
			// on ajoute le contact de livraison client � la commande fournisseur
			$sql= "Insert into ".MAIN_DB_PREFIX."element_contact";
			$sql.= " ( statut, fk_c_type_contact, element_id, fk_socpeople)";
			$sql.= " values (4, 145, ".$cmdeFournId.", ".$fk_socpeople.")";
			dol_syslog(get_class($this)."::add_contact_delivery_client insert sql=".$sql);
			$resqlinsert = $this->db->query($sql);
			return 1;
		}
		return 0;
	}

	function get_array_product_prop($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;
		
		// on récupère les products des propales
		$sql = 'SELECT DISTINCT prd.fk_product, sum(prd.qty) as nbProp';
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as prd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.rowid = prd.fk_propal";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_fourn > 0)
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON prd.fk_product = pfp.fk_product";
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON prd.fk_product = cp.fk_product"; 
		$sql.= " WHERE pr.entity = ".$conf->entity;
		$sql.= " AND prd.fk_product >0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND prd.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND prd.product_type =1";
				break;
		}

		$sql.= " AND pr.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY prd.fk_product";
		dol_syslog(get_class($this)."::get_array_product_prop sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++) {
					if ($tblRestock[$j]->id == $objp->fk_product) {
						$lineofproduct=$j;
						//exit for;
					}
				}
				// si le produit est déjà dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbPropDraft+= $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbPropValidate+= $objp->nbProp;
					else
						$tblRestock[$lineofproduct]->nbPropSigned+= $objp->nbProp;
				} else {
					// sinon on ajoute un nouveau produit dans le tableau
					$tblRestock[$numlines] = new Restock($this->db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbPropDraft = $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbPropValidate = $objp->nbProp;
					else
						$tblRestock[$numlines]->nbPropSigned = $objp->nbProp;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function enrichir_product($tblRestock)
	{
		global $conf;
		
		$numlines=count($tblRestock);
		// on boucle sur les lignes de produit à commander
		for ($i = 0 ; $i < $numlines ; $i++) {
			// on récupère les infos des produits 
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, p.desiredstock,';
			$sql.= ' MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";

			$sql.= " WHERE p.rowid=".$tblRestock[$i]->id;
			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, p.desiredstock';
			

			dol_syslog(get_class($this)."::enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			// si le produit est commandable chez un fournisseur
			if ($resql) {
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->ref_product=		$objp->ref;
				$tblRestock[$i]->libproduct=		$objp->label;
				$tblRestock[$i]->prixVenteHT=		$objp->price;
				$tblRestock[$i]->prixAchatHT=		$objp->minsellprice;
				$tblRestock[$i]->onBuyProduct=		$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->stockQty=			$objp->stock;
				$tblRestock[$i]->stockQtyAlert=		$objp->seuil_stock_alerte;
				$tblRestock[$i]->stockQtyDesired=	$objp->desiredstock;

				// on calcul ici le prix de vente unitaire réel
				if ($tblRestock[$i]->nbCmdeClient > 0)
					$tblRestock[$i]->prixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
			}
			
			// on regarde si il n'y pas de commande fournisseur en cours
			$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
			$sql.= " WHERE cof.entity = ".$conf->entity;
			// on récup les cmd en cours ainsi que les partiels que l'on déduira ensuite
			$sql.= " AND (cof.fk_statut = 3 OR cof.fk_statut = 4)";
			$sql.= " AND cofd.fk_product=".$tblRestock[$i]->id;
			dol_syslog(get_class($this)."::enrichir_product::cmde_fourn sql=".$sql);
			//print $sql.'<br>';
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->nbCmdFourn= $objp->nbCmdFourn;

				//recues partiellements
				$sql = "SELECT SUM(qty) AS nbCmdFournRecues";
				$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur AS c, ".MAIN_DB_PREFIX."commande_fournisseur_dispatch AS cd";
				$sql.= " WHERE c.fk_statut = 4";
				$sql.= " AND c.rowid = cd.fk_commande ";
				$sql.= " AND cd.fk_product=".$tblRestock[$i]->id;
				$resql = $this->db->query($sql);
				if ($resql) {
					$objp = $this->db->fetch_object($resql);
					$tblRestock[$i]->nbCmdFourn -= $objp->nbCmdFournRecues;
				}
			}
		}
		return $tblRestock;
	}

	// recherche récursive des composants avec filtrage sur les catégories
	function getcomponent($fk_parent, $qty, $search_categ=0, $search_fourn=0, $maxlevel=0)
	{
		global $conf;
		global $originId;
		$components=array();
		$nbcomponent=0;
		// on regarde si factory est installé
		if ($conf->global->MAIN_MODULE_FACTORY) {
			
			$recursivitedeep=!empty($conf->global->RESTOCK_RECURSIVITY_DEEP)?$conf->global->RESTOCK_RECURSIVITY_DEEP:0;
			
			// si on est pas dans trop loin dans la récursivité
			if ($recursivitedeep != "" && $maxlevel > $recursivitedeep) {
				print $langs->trans("RecursivityLimitReached", $fk_parent)." <br>";
				return $components;
			}
			
			$sql = 'SELECT fk_product_children as fk_product_fils, qty from '.MAIN_DB_PREFIX.'product_factory';
			if ($search_fourn > 0)
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON fk_product_children = pfp.fk_product";
			// We'll need this table joined to the select in order to filter by categ
			if ($search_categ > 0) 
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON fk_product_children = cp.fk_product"; 
			$sql.= ' WHERE fk_product_father  = '.$fk_parent;
			if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
			if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
				
			//print $sql.'<br>';
			$res = $this->db->query($sql);
			if ($res) {
				$num = $this->db->num_rows($res);

				if ($num > 0) {
					
					$i=0;
					while ($i < $num) {
						$objp = $this->db->fetch_object($res);

						if (!empty($conf->global->RESTOCK_FACTORY_RECURSIVE_OF)) {
							$tblcomponent=$this->getcomponent(
											$objp->fk_product_fils, $objp->qty, 
											$search_categ, $search_fourn, 
											$maxlevel++
							);
							foreach ($tblcomponent as $lgncomponent) {
								$lineofproduct =-1;
								// on regarde si on trouve déjà le produit dans le tableau 
								for ($j = 0 ; $j < $nbcomponent ; $j++)
									if ($components[$j][0] == $lgncomponent[0])
										$lineofproduct=$j;
					
								if ($lineofproduct >= 0)  {
									// on multiplie par la quantité du composant
									$components[$lineofproduct][1]+= $lgncomponent[1]*$qty;
	
								} else {
									// on ajoute le composant trouvé au tableau des composants
									$components[$nbcomponent][0]=$lgncomponent[0];
									$components[$nbcomponent][1]=$lgncomponent[1]*$qty;
									$components[$nbcomponent][2]=count($lgncomponent)==2?$lgncomponent[2]:0;
									$nbcomponent++;
								}
							}
						} else {
							$components[$nbcomponent][0]=$objp->fk_product_fils;
							$components[$nbcomponent][1]=$objp->qty;
							$components[$nbcomponent][2]=0;
							$nbcomponent++;
						}
						$i++;
					}
				}
			}
		}
		$tblcomponent="";
		// dans les autres cas (produits virtuels ou de base
		if (!empty($conf->global->PRODUIT_SOUSPRODUITS)) {

			$recursivitedeep=!empty($conf->global->RESTOCK_RECURSIVITY_DEEP)?$conf->global->RESTOCK_RECURSIVITY_DEEP:"";

			// si on est pas dans trop loin dans la récursivité
			if ($recursivitedeep != "" && $maxlevel > $recursivitedeep) {
				print $langs->trans("RecursivityLimitReached", $fk_parent)." <br>";
				return $components;
			}

			// On regarde dans les produits virtuels 
			$sql = 'SELECT fk_product_fils, qty ';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product_association';
			if ($search_fourn > 0)
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON fk_product_fils = pfp.fk_product";
			// We'll need this table joined to the select in order to filter by categ
			if ($search_categ > 0) 
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON fk_product_fils = cp.fk_product"; 

			$sql.= ' WHERE fk_product_pere  = '.$fk_parent;
			if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
			if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;

			$res = $this->db->query($sql);
			if ($res) {
				$num = $this->db->num_rows($res);
				if ($num > 0) {
					// si le produit a des composants
					$i=0;
					while ($i < $num) {
						$objp = $this->db->fetch_object($res);
						// on regarde récursivement si les composants ont eux-même des composants
						$tblcomponent=$this->getcomponent(
										$objp->fk_product_fils, $objp->qty, 
										$search_categ, $search_fourn, 
										$maxlevel++
						);
						foreach ($tblcomponent as $lgncomponent) {
							$lineofproduct =-1;
							// on regarde si on trouve déjà le produit dans le tableau 
							for ($j = 0 ; $j < $nbcomponent ; $j++)
								if ($components[$j][0] == $lgncomponent[0])
									$lineofproduct=$j;
				
							if ($lineofproduct >= 0) {
								// si on a trouvé le produit
								// on multiplie par la quantité du composant
								$components[$lineofproduct][1]+= $lgncomponent[1]*$qty;
							} else {
								// on ajoute le composant trouvé au tableau des composants
								$components[$nbcomponent][0]=$lgncomponent[0];
								$components[$nbcomponent][1]=$lgncomponent[1]*$qty;
								$nbcomponent++;
							}
						}
						$i++;
					}
				}
			}
		}
		// si pas d'enfant, c'est un produit de base, il est sont propre composant unique
		if ($components == array() || (!empty($conf->global->RESTOCK_FACTORY_RECURSIVE_OF) && $originId !=$fk_parent)) {
			$components[$nbcomponent][0]=$fk_parent;
			$components[$nbcomponent][1]=$qty;
			// on mémorise le nombre de sous-composant
			if (is_array($tblcomponent))
				$components[$nbcomponent][2]=$nbcomponent;
		}

		return $components;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
	    $error = 0;

	    dol_syslog(__METHOD__, LOG_DEBUG);

	    $object = new self($this->db);

	    $this->db->begin();

	    // Load source object
		$result = $object->fetch($fromid);

	    // get lines so they will be clone
	    //foreach($this->lines as $line)
	    //	$line->fetch_optionals();

	    // Reset some properties
	    unset($object->id);
	    unset($object->fk_user_creat);
	    unset($object->import_key);


	    // Clear fields
	    $object->ref = empty($this->fields['ref']['default']) ? "copy_of_".$object->ref : $this->fields['ref']['default'];
	    $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
	    $object->status = self::STATUS_DRAFT;
	    // ...
	    // Clear extrafields that are unique
	    if (is_array($object->array_options) && count($object->array_options) > 0)
	    {
	    	$extrafields->fetch_name_optionals_label($this->table_element);
	    	foreach ($object->array_options as $key => $option)
	    	{
	    		$shortkey = preg_replace('/options_/', '', $key);
	    		if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
	    		{
	    			//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
	    			unset($object->array_options[$key]);
	    		}
	    	}
	    }

	    // Create clone
		$object->context['createfromclone'] = 'createfromclone';
	    $result = $object->createCommon($user);
	    if ($result < 0) {
	        $error++;
	        $this->error = $object->error;
	        $this->errors = $object->errors;
	    }

		// lets clone line
	    //foreach($object->lines as $line)
		//	$object->addLine($line);

		//var_dump($this->lines);
		//var_dump($object->lines);
		//exit;

	    if (!$error) {
	    	// copy internal contacts
	    	if ($this->copy_linked_contact($object, 'internal') < 0)
	    	{
	    		$error++;
	    	}
	    }

	    if (!$error)
	    {
	    	// copy external contacts if same company
	    	if (property_exists($this, 'socid') && $this->socid == $object->socid)
	    	{
	    		if ($this->copy_linked_contact($object, 'external') < 0)
	    			$error++;
	    	}
	    }

	    unset($object->context['createfromclone']);

	    // End
	    if (!$error) {
	        $this->db->commit();
	        return $object;
	    } else {
	        $this->db->rollback();
	        return -1;
	    }
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{

		if (empty($id) && empty($ref) && empty($morewhere)) return -1;

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.$this->table_element;

		if (!empty($id))  $sql .= ' WHERE rowid = '.$id;
		elseif (!empty($ref)) $sql .= " WHERE ref = ".$this->quote($ref, $this->fields['ref']);

		if (empty($id) && isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) 
			$sql .= ' AND entity IN ('.getEntity($this->table_element).')';
		$sql .= ' LIMIT 1'; // This is a fetch, to be sure to get only one record
		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj) {
				$this->rowid = $obj->rowid;
				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->label = $obj->label;
				$this->origin_type = $obj->origin_type;
				$this->fk_originid = $obj->fk_originid;
				$this->fk_project = $obj->fk_project;

				// on récupère l'object à l'origin du restock si il y en a un
				if ($obj->fk_originid > 0) {
					switch($obj->origin_type) {
						case "product" :
							require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
							$this->elementOrigin = new Product($this->db);
							$this->elementOrigin->fetch($obj->fk_originid);
							break;
						case "factory" :
								dol_include_once('/factory/class/factory.class.php');
								$this->elementOrigin = new Factory($this->db);
								$this->elementOrigin->fetch($obj->fk_originid);
								break;
						case "order" :
							require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$this->elementOrigin = new Commande($this->db);
							$this->elementOrigin->fetch($obj->fk_originid);
							break;
		
						}
				}
				$this->description = $obj->description;
				$this->note_public = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->date_creation = strtotime($obj->date_creation);
				$this->tms = strtotime($obj->tms);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;

				$this->import_key = $obj->import_key;
				$this->model_pdf = $obj->model_pdf;
				$this->status = $obj->status;

				// pour récupérer le nombre d'élement crée
				$this->get_NB_of_restocktype();

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$result= $this->id;
			} else
				$result= 0;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$result= -1;
		}

		if ($result > 0 && !empty($this->table_element_line)) 
			$this->fetchLines();
		return $result;
	}

	public function get_NB_of_restocktype()
	{
		$sql = 'SELECT element_type, count(*) as nb, sum(quantity) as sumqty FROM '.MAIN_DB_PREFIX.'restockdet';
		$sql .= ' WHERE fk_restock = '.$this->id;
		$sql .= ' GROUP BY element_type';

		$res = $this->db->query($sql);
		if ($res) {
			$num = $this->db->num_rows($res);
			$i = 0;
			$this->nbof = null;
			$this->nborder = null;
			$this->nbproposal = null;
			$this->sumQtyOf = null;
			$this->sumQtyOrder = null;
			$this->sumQtyProposal = null;

			while ($i < $num) {
				$obj = $this->db->fetch_object($res);
				switch($obj->element_type) {
					case "factory" :
						$this->nbof = $obj->nb;
						$this->sumQtyOf = $obj->sumqty;
						break;
					case "order" :
						$this->nborder = $obj->nb;
						$this->sumQtyOrder = $obj->sumqty;
						break;
					case "proposal" :
						$this->nbproposal = $obj->nb;
						$this->sumQtyProposal = $obj->sumqty;
						break;
					}
				$i++;
			}
		}
	}


	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();
		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
            $i = 0;
			while ($i < min($limit, $num))
			{
			    $obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}


	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0)
		{
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *	Validate object
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$status			3 states of status
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $status, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		// empty should not happened, but when it occurs, the test save life
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
			$num = $this->getNextNumRef();
		else
			$num = $this->ref;

		$this->newref = $num;

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET ref = '".$this->db->escape($num)."'";
		$sql .= " , status = ".$status;
		$sql .= ", tms = now()";
		$sql .= ", fk_user_modif = ".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('RESTOCK_VALIDATE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error ) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'restock/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'restock/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) { $error++; $this->error = $this->db->lasterror(); }

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->restock->dir_output.'/restock/'.$oldref;
				$dirdest = $conf->restock->dir_output.'/restock/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->restock->dir_output.'/restock/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}
		// Set new ref and current status
		if (!$error)
		{
			$this->ref = $num;
			$this->status = $status;
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_WAITING)
		{
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->restock->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->restock_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'RESTOCK_CLOSE');
	}

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

        $result = '';

        $label = '<u>'.$langs->trans("restock").'</u>';
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
        if (isset($this->status)) {
        	$label.= '<br><b>' . $langs->trans("Status").":</b> ".$this->getLibStatut(5);
        }

        $url = dol_buildpath('/restock/card.php', 1).'?id='.$this->id;

        if ($option != 'nolink')
        {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
            if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        }

        $linkclose = '';
        if (empty($notooltip))
        {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("ShowRestock");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        }
        else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('restockdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
    }

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@param	  int	$noentities 	0=use classic translation, 1=use noentities translation (for pdf print)
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0, $noentities=0)
	{
		return $this->LibStatut($this->status, $mode, $noentities);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@param	  int	$noentities 	0=use classic translation, 1=use noentities translation (for pdf print)
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0, $noentities=0)
	{
		global $langs;
		if ($noentities == 1) 
			return $langs->transnoentities($this->arrayStatut[$status]);

		$this->labelStatus[$status] = $langs->trans($this->arrayStatut[$status]);
		$this->labelStatusShort[$status] = $langs->trans($this->arrayStatut[$status]);
	
		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) $statusType = 'status6';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);

	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray($elementType="")
	{
	    $this->lines = array();
		$arrayFilter = array('customsql'=>'fk_restock = '.$this->id);
		// add array filter to filter on element type
		if ($elementType) 
			$arrayFilter['elementtype'] = $elementType;

	    $objectline = new restockLine($this->db);
	    $result = $objectline->fetchAll('', '', 0, 0, $arrayFilter);

		if (is_numeric($result))
	    {
	        $this->error = $this->error;
	        $this->errors = $this->errors;
	        return $result;
	    }
	    else
	    {
	        $this->lines = $result;
	        return $this->lines;
	    }
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("restock@restock");

		if (empty($conf->global->RESTOCK_ADDON)) {
			$conf->global->RESTOCK_ADDON = 'mod_restock_standard';
		}

		if (!empty($conf->global->RESTOCK_ADDON))
		{
			$mybool = false;

			$file = $conf->global->RESTOCK_ADDON.".php";
			$classname = $conf->global->RESTOCK_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir)
			{
				$dir = dol_buildpath($reldir."core/modules/restock/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false)
			{
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname($this->db);
			$numref = $obj->getNextValue($this);

			if ($numref != "")
			{
				return $numref;
			}
			else
			{
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_RESTOCK_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("restock@restock");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->RESTOCK_ADDON_PDF)) {
				$modele = $conf->global->RESTOCK_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/restock/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	function setProjet($fk_project) {
		global $user;
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET fk_project = ".$fk_project;
		$sql .= ", tms = now()";
		$sql .= ", fk_user_modif = ".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::setProjet sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		} else
			$this->fetch($this->id);

	}

	function addLine($fk_restock, $fk_elementid, $element_type, $quantity=0, $progress=0, $fk_product=0) {

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql .= ' (fk_restock, fk_elementid, element_type, progress, quantity, fk_product)';
		$sql .= ' VALUES ('.$fk_restock.', '.$fk_elementid.', "'.$element_type.'"';
		$sql .= ', '.price2num($progress).', '.price2num($quantity).','.$fk_product.')';
		dol_syslog(get_class($this)."::adline() sql=".$sql, LOG_DEBUG);
		if (! $this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		}
		else
			return 1;

	}

}

class restockLine  extends CommonObject
{
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'visible'=>-1, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_restock' => array('type'=>'integer:Restock:restock/class/restock.class.php', 'label'=>'restock', 'enabled'=>1, 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'restock.rowid',),
		'fk_elementid' => array('type'=>'integer', 'label'=>'ElementId', 'enabled'=>1, 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'element_type' => array('type'=>'varchar(32)', 'label'=>'ElementType', 'enabled'=>1, 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'position'=>60, 'notnull'=>0, 'visible'=>-3,),
		'progress' => array('type'=>'integer', 'label'=>'Progress', 'enabled'=>1, 'position'=>511, 'notnull'=>0, 'visible'=>1,),
		'date_end_planned' => array('type'=>'datetime', 'label'=>'DateEndPlanned', 'enabled'=>1, 'position'=>515, 'notnull'=>0, 'visible'=>1,),
		'fk_product' => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'enabled'=>1, 'position'=>516, 'notnull'=>0, 'visible'=>1,),
		'quantity' => array('double'=>'integer', 'label'=>'Quantity', 'enabled'=>1, 'position'=>520, 'notnull'=>0, 'visible'=>1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
	);

	public $rowid;
	public $fk_restock;
	public $fk_elementid;
	public $element_type;
	public $elementObject;	// l'object crée par restock

	public $description;	// infos associé à la progression
	public $progress;		// indicateur de progression
	public $date_end_planned; // date de fin prévue de l'élément
	public $quantity;		// quantité commandé sur l'élément
	public $tms;
	public $import_key;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'restockdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'restockdet';
	/**
	 * @var int  Does restock support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for restock. Must be the part after the 'object_' into object_restock.png
	 */
	public $picto = 'restock@restock';



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."restockdet";
		$sql.= " SET progress=".$this->progress;
		$sql.= " , date_end_planned = ".($this->date_end_planned?"'".$this->db->idate($this->date_end_planned)."'":"null");
		$sql.= " , description = '".$this->db->escape($this->description)."'";
		$sql.= " WHERE rowid=".$this->id;
		//print $sql."<br>";
		$this->db->query($sql);

		return 1;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function updateProduct($fk_product)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."restockdet";
		$sql.= " SET fk_product=".$fk_product;
		$sql.= " WHERE rowid=".$this->id;
		$this->db->query($sql);
		$this->fk_product = $fk_product;
		return 1;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				elseif ($key == 'elementfilter') {
					$sqlwhere[] = ' element_type = "'.$this->db->escape($value).'"';
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		} else
		{ 
			$sql .= " ORDER BY element_type, fk_elementid";
		}

		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);
				switch ($obj->element_type) {
					case 'factory':
						dol_include_once('/factory/class/factory.class.php');
						$record->elementObject = new Factory($this->db);
						$record->elementObject->fetch($obj->fk_elementid);
						break;
					case 'order':
						require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
						$record->elementObject = new CommandeFournisseur($this->db);
						$record->elementObject->fetch($obj->fk_elementid);
						$record->elementObject->fetch_thirdparty();

						break;
					case 'proposal':
						require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
						$record->elementObject = new SupplierProposal($this->db);
						$record->elementObject->fetch($obj->fk_elementid);
						$record->elementObject->fetch_thirdparty();
						break;
				}

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


}

class RestockCmde
{
	// nouveau champs pour les commandes liées
	var $fk_commandedet;
	var $fk_product;
	var $fk_commande;
	var $date_commande;
	var $qty;
	var $ref_product;
	var $libproduct;
	var $prixVenteHT;
	var $prixAchatHT;
	var $onBuyProduct;
	var $fk_product_type;
	var $stockQty;
	var $stockQtyAlert;
	var $nbCmdFourn;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
	
	function get_array_product_cmdedet($tblRestock, $search_categ, $search_fourn, $morefilter="", $year="", $month="")
	{
		global $conf;

		// on récupère les products des commandes
		$sql = 'SELECT co.rowid as fk_commande, co.date_commande, cod.rowid as fk_commandedet,';
		$sql.= ' cod.fk_product, cod.qty as nbCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commande as co ";
		$sql.= " , ".MAIN_DB_PREFIX."commandedet as cod";
		if ($search_fourn > 0)
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ > 0)
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product"; 
		$sql.= " WHERE co.entity = ".$conf->entity;
		$sql.= " AND cod.fk_product >0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND cod.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND cod.product_type =1";
				break;
		}

		$sql.= " AND co.rowid = cod.fk_commande";
		$sql.= " AND (cod.fk_commandefourndet is null OR cod.fk_commandefourndet =0)";
		
		if ($morefilter)
			$sql.= " AND ".$morefilter;
		
		if ($month > 0) {
			if ($year > 0 ) {
				$sql.= " AND co.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."'";
				$sql.= " AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			} else
				$sql.= " AND date_format(co.date_commande, '%m') = '".$month."'";
		} elseif ($year > 0) {
			$sql.= " AND co.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."'";
			$sql.= " AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		}

		
		if ($search_fourn > 0)   	$sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   	$sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) 	$sql.= " AND cp.fk_categorie IS NULL";
		
		dol_syslog(get_class($this)."::get_array_product_cmdedet sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);

				// sinon on ajoute une ligne dans le tableau
				$tblRestock[$i] = new RestockCmde($this->db);
				$tblRestock[$i]->fk_product= $objp->fk_product;
				$tblRestock[$i]->fk_commandedet= $objp->fk_commandedet;
				$tblRestock[$i]->fk_commande= $objp->fk_commande;
				$tblRestock[$i]->date_commande= $objp->date_commande;
				$tblRestock[$i]->qty = $objp->nbCmde;

				$i++;
			}
		}
		return $tblRestock;
	}
	
	function enrichir_product($tblRestock)
	{
		global $conf;
		
		$numlines=count($tblRestock);
		for ($i = 0 ; $i < $numlines ; $i++) {
			// on récupère les infos des produits 
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, pfp.remise_percent, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";
			$sql.= " WHERE p.rowid=".$tblRestock[$i]->fk_product;
			if (! empty($conf->product->enabled) && ! empty($conf->service->enabled)) {
				if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT == 1)
					$sql.= " AND p.fk_product_type = 0";	// product
				elseif ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT == 2)
					$sql.= " AND p.fk_product_type = 1";  // service
			}

			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, pfp.remise_percent';

			dol_syslog(get_class($this)."::enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);

				$tblRestock[$i]->ref_product=	$objp->ref;
				$tblRestock[$i]->libproduct=	$objp->label;
				$tblRestock[$i]->prixVenteHT=	$objp->price;
				$tblRestock[$i]->prixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->onBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->stockQty= 		$objp->stock;
				$tblRestock[$i]->stockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
				//	if ($tblRestock[$i]->nbCmdeClient > 0)
				//		$tblRestock[$i]->prixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
			}

			// on regarde si il n'y pas de commande fournisseur en cours
			$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
			$sql.= " WHERE cof.entity = ".$conf->entity;
			$sql.= " AND cof.fk_statut = 3";
			$sql.= " and cofd.fk_product=".$tblRestock[$i]->fk_product;
			dol_syslog(get_class($this)."::enrichir_product::cmde_fourn sql=".$sql);
			// print $sql."<br>";
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->nbCmdFourn= $objp->nbCmdFourn;
			}
		}
		return $tblRestock;
	}
	
	function fetchdet($fk_commandedet, $qtysel)
	{
		$sql = 'SELECT co.rowid as fk_commande, co.date_commande, cod.rowid as fk_commandedet, cod.fk_product';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " ,".MAIN_DB_PREFIX."commande as co ";
		$sql.= " WHERE cod.rowid = ".$fk_commandedet;
		$sql.= " and co.rowid = cod.fk_commande";
		dol_syslog(get_class($this)."::fetchdet sql=".$sql);
		// print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$objp = $this->db->fetch_object($resql);

			$this->fk_product= $objp->fk_product;
			$this->fk_commandedet= $objp->fk_commandedet;
			$this->fk_commande= $objp->fk_commande;
			$this->date_commande= $objp->date_commande;
			$this->qty = $qtysel;

			// on récupère les infos des produits 
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";
			$sql.= " WHERE p.rowid=".$objp->fk_product;
			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type';
			
			dol_syslog(get_class($this)."::fetchdet_enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);

				$this->ref_product=	$objp->ref;
				$this->libproduct=	$objp->label;
				$this->prixVenteHT=	$objp->price;
				$this->prixAchatHT=	$objp->minsellprice;
				$this->onBuyProduct=	$objp->tobuy;
				$this->fk_product_type=	$objp->fk_product_type;
				$this->stockQty= 		$objp->stock;
				$this->stockQtyAlert=	$objp->seuil_stock_alerte;
			}
		}
	}
	
	function deletelink($fk_commandefourn, $user)
	{
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

		// on se positionne sur la commande fournisseur
		$cfdelete = new CommandeFournisseur($this->db);
		$cfdelete->fetch($fk_commandefourn);

		// on boucle sur les lignes la commande fournisseur
		foreach ($cfdelete->lines as $cfdetline) {
			// on remet à zéro la ligne de la commande client associé
			$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet";
			$sql.= " SET fk_commandefourndet=0";
			$sql.= " WHERE fk_commandefourndet=".$cfdetline->id;
			$this->db->query($sql);
		}

		// on lance enfin la suppression de la commande fournisseur
		$cfdelete->delete($user);

		return 1;
	}
}