<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2019 Francis Appels <francis.appels@z-application.com>
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
 * \file        class/backorder_supplier.class.php
 * \ingroup     backorder
 * \brief       This file is a class file for show BackOrder of supplier order
 */

// Put here all includes required by your class file
require_once 'backorder.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

/**
 * Class for BackOrder
 */
class BackOrderSupplier extends BackOrder
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'backorder_supplier';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'backorder_backorder_supplier';

	/** @var string $table_element_reception_line table of order reception line */
	public $table_element_reception_line = 'receptiondet_batch';

	/** @var string $key_ship_line_order key of linked order to ship/receive line */
	public $key_ship_line_order = 'fk_element';

	/** @var string $key_ship_line_order_line key of linked order line to ship/receive line */
	public $key_ship_line_order_line = 'fk_elementdet';

	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'fk_product' => array('type'=>'integer', 'label'=>'Product', 'enabled'=>1, 'visible'=>1, 'position'=>1, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'search'=>'p.ref', 'help'=>"LinkToProduct", 'comment'=>"Product id, display as product url",),
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>0, 'position'=>10, 'notnull'=>1, 'index'=>1,),
		'fk_commandedet' => array('type'=>'integer', 'label'=>'OrderLine', 'enabled'=>1, 'visible'=>1, 'position'=>15, 'notnull'=>1, 'index'=>1, 'comment'=>"Order line id, display as line label",),
		'fk_commande' => array('type'=>'integer', 'label'=>'Order', 'enabled'=>1, 'visible'=>1, 'position'=>20, 'notnull'=>1, 'searchall'=>1,'search'=>'c.ref', 'help'=>"LinkToOrder", 'comment'=>"Customer order, show as order url",),
		'ref_supplier' => array('type'=>'varchar(255)', 'label'=>'RefSupplier', 'enabled'=>1, 'visible'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1,'search'=>'t.ref_supplier', 'help'=>"Help text",),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'help'=>"LinkToThirparty",),
		'fk_projet' =>array('type'=>'integer:Project:projet/class/project.class.php', 'label'=>'Project', 'enabled'=>"isModEnabled('project')", 'visible'=>-1,'search'=>'pj.ref', 'position'=>55),
		'fk_user_author' => array('type'=>"integer:User:user/class/user.class.php", 'label'=>'UserAuthor', 'picto'=>"user", 'enabled'=>"isModEnabled('user')", 'position'=>57, 'notnull'=>1, 'visible'=>'-1', 'foreignkey'=>'0', 'csslist'=>'tdoverflowmax150',),
		'town' => array('type'=>'varchar(64)', 'label'=>'Town', 'enabled'=>1, 'visible'=>1, 'position'=>60, 'notnull'=>-1,),
		'zip' => array('type'=>'varchar(64)', 'label'=>'Zip', 'enabled'=>1, 'visible'=>1, 'position'=>70, 'notnull'=>-1,),
		'fk_departement' => array('type'=>'integer', 'label'=>'State', 'enabled'=>1, 'visible'=>1, 'position'=>80, 'notnull'=>-1,),
		'fk_pays' => array('type'=>'integer', 'label'=>'Country', 'enabled'=>1, 'visible'=>1, 'position'=>90, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>1, 'position'=>100, 'notnull'=>1,),
		'date_livraison' => array('type'=>'datetime', 'label'=>'DateDeliveryPlanned', 'enabled'=>1, 'visible'=>1, 'position'=>110, 'notnull'=>-1,),
		'price' => array('type'=>'double(24,8)', 'label'=>'Price', 'enabled'=>1, 'visible'=>-1, 'position'=>120, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"order line price HT with discount",),
		'qty' => array('type'=>'double(24,8)', 'label'=>'OrderQty', 'enabled'=>1, 'visible'=>-1, 'position'=>120, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"order line qty",),
		'eqty' => array('type'=>'double(24,8)', 'label'=>'DispatchedQty', 'enabled'=>1, 'visible'=>-1, 'position'=>120, 'notnull'=>-1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"dispatch line qty",),
		'line_amount' => array('type'=>'double(24,8)', 'label'=>'LineAmount', 'enabled'=>1, 'visible'=>1, 'position'=>120, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder line amount",),
		'order_amount' => array('type'=>'double(24,8)', 'label'=>'OrderAmount', 'enabled'=>1, 'visible'=>1, 'position'=>120, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder order amount",),
		'line_qty' => array('type'=>'double(24,8)', 'label'=>'LineBackorderQty', 'enabled'=>1, 'visible'=>1, 'position'=>125, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder line qty",),
		'order_qty' => array('type'=>'double(24,8)', 'label'=>'OrderBackorderQty', 'enabled'=>1, 'visible'=>1, 'position'=>126, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder order qty",),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		'entity' => array('type'=>'sellist:entity:label:rowid', 'label'=>'Entity', 'enabled'=>1, 'visible'=>-2, 'notnull'=> 1, 'default'=>1, 'index'=>1, 'position'=>502),
	);
	public $getBackorderFields=array(
		'fk_product' => array('name'=>'cd.fk_product'),
		'fk_commandedet' => array('name'=>'cd.rowid as fk_commandedet'),
		'fk_commande' => array('name'=>'cd.fk_commande'),
		'commande_statut' => array('name'=>'c.fk_statut as commande_statut'),
		'ref_supplier' => array('name'=>'cd.ref as ref_supplier'),
		'fk_soc' => array('name'=>'c.fk_soc'),
		'fk_projet' => array('name'=>'c.fk_projet'),
		'fk_user_author' => array('name'=>'c.fk_user_author'),
		'town' => array('name'=>'s.town'),
		'zip' => array('name'=>'s.zip'),
		'fk_departement' => array('name'=>'s.fk_departement'),
		'fk_pays' => array('name'=>'s.fk_pays'),
		'date_creation' => array('name'=>'c.date_creation'),
		'date_livraison' => array('name'=>'c.date_livraison'),
		'price' => array('name'=>'cd.subprice as price'),
		'qty' => array('name'=>'cd.qty'),
		'eqty' => array('name'=>'SUM(d.qty) as eqty'),
		'entity' => array('name'=>'c.entity'),
		'amount' => array('type'=>'double(24,8)')
	);

	// END MODULEBUILDER PROPERTIES



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);
		$this->entityElement = 'supplier_order';
		if ((int) DOL_VERSION < 20) {
			$this->table_element_reception_line = 'commande_fournisseur_dispatch';
			$this->key_ship_line_order = 'fk_commande';
			$this->key_ship_line_order_line = 'fk_commandefourndet';
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param bool   $mode					$mode read, update or delete
	 * @param int    $fk_commande			Id of order
	 * @param int    $fk_commandedet		Id of order line
	 * @param int    $fk_reception_delete	Id of deleted reception (delete trigger is post commit trigger)
	 * @param float  $delete_qty			deleted qty from trigger.
	 *
	 * @return int         <0 if KO, 0 if not found, 1 if object fetched, > 1 if total ammount fetched
	 */
	public function getBackorder($mode, $fk_commande = null, $fk_commandedet = null, $fk_reception_delete = null, $delete_qty = null)
	{
		global $conf, $user;

		$countRecords = 0;
		$records = array();
		$idArray = array();

		if (isset($fk_commande)) {
			if ((int) DOL_VERSION >= 20) {
				$filter = '(fk_commande:=:'.$fk_commande.')';
				if (isset($fk_commandedet)) {
					$filter = '('.$filter.' AND (fk_commandedet:=:'.$fk_commandedet.'))';
				}
			} else {
				if (isset($fk_commandedet)) {
					$filter = array('customsql' => 'fk_commande = ' . ((int) $fk_commande) . ' AND fk_commandedet = ' . ((int) $fk_commandedet));
				} else {
					$filter = array('customsql' => 'fk_commande = ' . ((int) $fk_commande));
				}
			}
			$records = $this->fetchAll('', '', 0, 0, $filter);
			if (is_array($records)) {
				$countRecords = count($records);
				$deletedArray = array();
				if ($countRecords && ($mode == 'delete' || $mode == 'order')) {
					foreach ($records as $key => $record) {
						$result = $record->delete($user);
						if ($result > 0) {
							$deletedArray[] = $result;
						}
					}
				}
				if (count($deletedArray) == $countRecords) {
					$countRecords = 0;
				}
			} else {
				return $records;
			}
		}

		if ($mode != 'delete' && (empty($countRecords) || $mode == 'update' || $mode == 'order')) {
			$this->line_amount = 0;
			$this->line_qty = 0;
			$backorderAmount = array();
			$backorderQty = array();
			$sql = 'SELECT ';
			$sql.= $this->getBackorderFieldList();
			$sql.= " FROM ".MAIN_DB_PREFIX.'commande_fournisseur'." as c";
			$sql.= $this->getBackorderFieldFrom($fk_reception_delete);
			if ($this->ismultientitymanaged == 1) $sql.= " WHERE c.entity IN (".getEntity($this->entityElement).")";
			else $sql.= " WHERE 1 = 1";
			if ($fk_commande > 0) {
				$sql.= " AND c.rowid = " . ((int) $fk_commande);
			} else {
				$sql.= ' AND c.fk_statut < '.CommandeFournisseur::STATUS_RECEIVED_COMPLETELY;
			}
			if (empty($conf->global->BACKORDER_SUPPLIER_ORDER_NONRECEIVE)) {
				// at least one dispatch for having a backorder
				if ($fk_commande > 0) {
					$sql.= " AND (SELECT COUNT(rowid) FROM ".MAIN_DB_PREFIX.$this->table_element_reception_line." rd WHERE rd.".$this->key_ship_line_order." = " . ((int) $fk_commande) . " AND rd.status > 0) > 0";
				} else {
					$sql.= " AND (SELECT COUNT(rowid) FROM ".MAIN_DB_PREFIX.$this->table_element_reception_line." rd WHERE rd.".$this->key_ship_line_order." = c.rowid AND rd.status > 0) > 0";
				}
				if (!empty($conf->reception->enabled)) {
					$sql.= ' AND (r.fk_statut > 0 OR d.fk_reception IS NULL)';
				}
			}

			if ($fk_commandedet > 0) $sql.= " AND cd.rowid = " . ((int) $fk_commandedet);
			$sql.= ' GROUP BY ';
			$sql.= $this->getBackorderFieldListGroupBy();
			if (!$fk_commande) $sql.= ' ORDER BY c.rowid';

			$resql=$this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				$lineAmount = 0;
				$lineQty = 0;
				if ($num) {
					while ($i < $num) {
						$arr = $this->db->fetch_array($resql);

						$obj = new BackOrderSupplier($this->db);
						foreach ($arr as $key => $value) {
							$obj->{$key} = $value;
						}

						if ($fk_commandedet && $delete_qty) {
							// update eqty with deleted qty
							$obj->eqty -= $delete_qty;
						}

						// set all backorder for each commande det
						$this->setVarsFromFetchObj($obj);
						$lineAmount = $this->getAmount($obj->price, $obj->qty, $obj->eqty);
						$lineQty = $this->getQty($obj->qty, $obj->eqty);
						if ($obj->commande_statut >= CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
							$backorderAmount[$obj->fk_commande] = 0;
							$backorderQty[$obj->fk_commande] = 0;
						} elseif ($fk_commandedet) {
							$order_amount = 0;
							$order_qty = 0;
							$line_amount = 0;
							$line_qty = 0;
							// get current values
							foreach ($records as $key => $record) {
								if ($record->fk_commande == $fk_commande) {
									$order_amount = $record->order_amount;
									$order_qty = $record->order_qty;
								}
								if ($record->fk_commandedet == $fk_commandedet) {
									$line_amount += $record->line_amount;
									$line_qty += $record->line_qty;
								}
							}
							// remove current line amount from order qty
							$order_amount -= $line_amount;
							$order_qty -= $line_qty;
							// add updated line amount and qty
							$backorderAmount[$obj->fk_commande] = $order_amount + $lineAmount;
							$backorderQty[$obj->fk_commande] = $order_qty + $lineQty;
						} elseif (isset($backorderAmount[$obj->fk_commande])) {
							$backorderAmount[$obj->fk_commande] += $lineAmount;
							$backorderQty[$obj->fk_commande] += $lineQty;
						} else {
							$backorderAmount[$obj->fk_commande] = $lineAmount;
							$backorderQty[$obj->fk_commande] = $lineQty;
						}
						if (empty($countRecords)) {
							if ($obj->qty - $obj->eqty != 0) {
								$this->line_amount=$lineAmount;
								$this->line_qty=$lineQty;
								$idArray[] = $this->create($user);
							}
						} else {
							foreach ($records as $key => $record) {
								if ($record->fk_commandedet == 0) $record->fk_commandedet = $this->fk_commandedet;
								if ($record->fk_commandedet == $this->fk_commandedet) {
									$record->line_amount = $lineAmount;
									$record->line_qty = $lineQty;
									$record->eqty = $obj->eqty;
									$record->qty = $obj->qty;
									if (isset($this->fields['fk_projet']['enabled'])) $record->fk_projet = $obj->fk_projet;
									if (isset($this->fields['fk_user_author']['enabled'])) $record->fk_user_author = $obj->fk_user_author;
									$result = $record->update($user);
									if ($result > 0) {
										$idArray[] = $record->id;
									} else {
										$this->error = $record->error;
										$this->errors[] = $this->error;
										$idArray[] = $result;
									}
								}
							}
						}
						$i++;
					}
				}

				// set order backorder amount
				foreach ($idArray as $id) {
					if ($id > 0) {
						if ($fk_commandedet && $fk_commande) {
							// update all order_amount and qty for order
							if ((int) DOL_VERSION >= 20) {
								$filter = '(fk_commande:=:'.$fk_commande.')';
							} else {
								$filter = array('customsql' => 'fk_commande = ' . ((int) $fk_commande));
							}
							$records = $this->fetchAll('', '', 0, 0, $filter);
							if (is_array($records)) {
								foreach ($records as $key => $record) {
									$record->order_amount = price2num($backorderAmount[$this->fk_commande], 'MT');
									$record->order_qty = price2num($backorderQty[$this->fk_commande], 'MT');
									$result = $record->update($user);
									if ($result < 0) {
										$this->error = $record->error;
										$this->errors[] = $this->error;
										return $result;
									}
								}
							} else {
								return $records;
							}
						} else {
							$this->fetch($id);
							$this->order_amount = price2num($backorderAmount[$this->fk_commande], 'MT');
							$this->order_qty = price2num($backorderQty[$this->fk_commande], 'MT');
							$result = $this->update($user);
							if ($result < 0) {
								$this->error = $record->error;
								$this->errors[] = $this->error;
								return $result;
							}
						}
					} else {
						return $id;
					}
				}
			} else {
				$this->error=$this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		} else {
			foreach ($records as $key => $value) {
				$result = $this->fetch($key);
				if ($result < 0) {
					return -2;
				}
			}
		}

		return count($idArray);
	}

	/**
	 * getBackorderFieldFrom build from sql for getBackorder method
	 *
	 * @param int    $fk_reception_delete   Id of deleted reception
	 * @return string sql from part
	 */
	public function getBackorderFieldFrom($fk_reception_delete = null)
	{
		global $conf;

		$sql = ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet cd ON cd.fk_commande = c.rowid AND c.fk_statut > '.CommandeFournisseur::STATUS_ACCEPTED;
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON c.fk_soc = s.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element_reception_line.' d ON d.'.$this->key_ship_line_order.' = c.rowid AND d.'.$this->key_ship_line_order_line.' = cd.rowid AND d.status > 0';
		if ($fk_reception_delete) $sql.= " AND d.fk_reception <> " . ((int) $fk_reception_delete);
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid AND p.fk_product_type = 0';
		if (empty($conf->global->BACKORDER_SUPPLIER_SERVICES)) $sql.= ' AND p.fk_product_type = 0';
		if (!empty($conf->reception->enabled)) {
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'reception as r ON d.fk_reception = r.rowid';
		}
		return $sql;
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	object	$object					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						No options available
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($object, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result = '';
		$orderline = new CommandeFournisseurLigne($this->db);
		$orderline->fetch($object->fk_commandedet);

		$label = '<u>' . $langs->trans("BackOrder") . '</u>';
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('RefSupplier') . ':</b> ' . $orderline->ref_supplier;
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('Description') . ':</b> ' . dolGetFirstLineOfText($orderline->desc);
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('qtytosend') . ':</b> ' . ($object->qty - $object->eqty);

		$linkclose='';
		if (empty($notooltip)) {
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label=$langs->trans("ShowBackOrder");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

			/*
			 $hookmanager->initHooks(array('backorderdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		} else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a' . $linkclose . '>';
		$linkend='</a>';

		$result .= $linkstart;
		if (!empty($orderline->product_libelle)) {
			$result .= $orderline->product_libelle;
		} elseif (!empty($orderline->product_label)) {
			$result .= $orderline->product_label;
		} else {
			$result .= 'product';
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action;
		$hookmanager->initHooks(array('backorderdao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	* Return origin object
	*
	* @param int $origintype Type origin
	*
	* @return object
	*/
	public function getOrigin($origintype)
	{
		$origin='';
		switch ($origintype) {
			case 'commande':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$origin = new CommandeFournisseur($this->db);
				break;
			case 'shipping':
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$origin = new Expedition($this->db);
				break;
			case 'product':
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$origin = new Product($this->db);
				break;
			case 'propal':
				require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
				$origin = new Propal($this->db);
				break;
			case 'member':
				require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$origin = new Adherent($this->db);
				break;
			case 'facture':
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$origin = new Facture($this->db);
				break;
			case 'contrat':
				require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
				$origin = new Contrat($this->db);
				break;
			case 'expensereport':
				require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
				$origin = new Expensereport($this->db);
				break;
			case 'fichinter':
				require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
				$origin = new Fichinter($this->db);
				break;
			case 'invoice_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$origin = new FactureFournisseur($this->db);
				break;
			case 'supplier_proposal':
				require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
				$origin = new SupplierProposal($this->db);
				break;
			case 'user':
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				$origin = new User($this->db);
				break;
			case 'contact':
				require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$origin = new Contact($this->db);
				break;
			default:
				break;
		}

		if (empty($origin) || ! is_object($origin)) return null;
		return $origin;
	}

	/**
	 * Action executed by scheduler
	 * @param string $param 'regenerate' to regenerate backorders.
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob($param = 'regenerate')
	{
		global $langs;

		$langs->loadLangs(array("backorder@backorder"));
		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__ . ' ' . $param, LOG_DEBUG);

		if ($this->deleteAll() > 0) {
			$result = $this->getBackorder('read');
			if ($result >= 0) {
				$this->output = $langs->trans("SupplierBackordersGenerated", $result);
			} else {
				$this->output = $langs->trans("generateBackordersFailed");
				$error --;
			}
		} else {
			$this->output = $langs->trans("deleteBackordersFailed");
			$error --;
		}

		return $error;
	}
}
