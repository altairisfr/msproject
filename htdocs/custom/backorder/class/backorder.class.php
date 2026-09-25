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
 * \file        class/backorder.class.php
 * \ingroup     backorder
 * \brief       This file is a class file for show BackOrder
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

/**
 * Class for BackOrder
 */
class BackOrder extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'backorder';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'backorder';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'backorder_backorder';
	/**
	 * @var int  Does backorder support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;
	/**
	 * @var int  Does backorder support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;
	/**
	 * @var string String with name of icon for backorder. Must be the part after the 'object_' into object_backorder.png
	 */
	public $picto = 'backorder@backorder';

	/** @var string $key_ship_line_order key of linked order to ship/receive line */
	public $key_ship_line_order = 'fk_element';

	/** @var string $key_ship_line_order_line key of linked order line to ship/receive line */
	public $key_ship_line_order_line = 'fk_elementdet';

	const STATUS_NOT_SHIPPABLE = 0;
	const STATUS_SHIPPABLE = 1;
	const STATUS_PARTLY_SHIPPABLE = 2;
	const STATUS_NOT_FULLY_SHIPPABLE = 3;

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
		'ref_client' => array('type'=>'varchar(255)', 'label'=>'RefCustomerOrder', 'enabled'=>1, 'visible'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1,'search'=>'t.ref_client', 'help'=>"Help text",),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1,'search'=>'s.nom', 'help'=>"LinkToThirparty",),
		'fk_projet' =>array('type'=>'integer:Project:projet/class/project.class.php', 'label'=>'Project', 'enabled'=>"isModEnabled('project')", 'visible'=>-1,'search'=>'pj.ref', 'position'=>55),
		'fk_user_author' => array('type'=>"integer:User:user/class/user.class.php", 'label'=>'UserAuthor', 'picto'=>"user", 'enabled'=>"isModEnabled('user')", 'position'=>57, 'notnull'=>1, 'visible'=>'-1', 'foreignkey'=>'0', 'csslist'=>'tdoverflowmax150',),
		'town' => array('type'=>'varchar(64)', 'label'=>'Town', 'enabled'=>1, 'visible'=>1, 'position'=>60, 'notnull'=>-1,),
		'zip' => array('type'=>'varchar(64)', 'label'=>'Zip', 'enabled'=>1, 'visible'=>1, 'position'=>70, 'notnull'=>-1,),
		'fk_departement' => array('type'=>'integer', 'label'=>'State', 'enabled'=>1, 'visible'=>1, 'position'=>80, 'notnull'=>-1,),
		'fk_pays' => array('type'=>'integer', 'label'=>'Country', 'enabled'=>1, 'visible'=>1, 'position'=>90, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>1, 'position'=>100, 'notnull'=>1,),
		'date_livraison' => array('type'=>'datetime', 'label'=>'DateDeliveryPlanned', 'enabled'=>1, 'visible'=>1, 'position'=>110, 'notnull'=>-1,),
		'price' => array('type'=>'double(24,8)', 'label'=>'Price', 'enabled'=>1, 'visible'=>-1, 'position'=>120, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"order line price HT with discount",),
		'qty' => array('type'=>'double(24,8)', 'label'=>'OrderQty', 'enabled'=>1, 'visible'=>-1, 'position'=>121, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"order line qty",),
		'eqty' => array('type'=>'double(24,8)', 'label'=>'ShippedQty', 'enabled'=>1, 'visible'=>-1, 'position'=>122, 'notnull'=>-1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"expedition line qty",),
		'line_amount' => array('type'=>'double(24,8)', 'label'=>'LineAmount', 'enabled'=>1, 'visible'=>1, 'position'=>123, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder line amount",),
		'order_amount' => array('type'=>'double(24,8)', 'label'=>'OrderAmount', 'enabled'=>1, 'visible'=>1, 'position'=>124, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder order amount",),
		'line_qty' => array('type'=>'double(24,8)', 'label'=>'LineBackorderQty', 'enabled'=>1, 'visible'=>1, 'position'=>125, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder line qty",),
		'order_qty' => array('type'=>'double(24,8)', 'label'=>'OrderBackorderQty', 'enabled'=>1, 'visible'=>1, 'position'=>126, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder order qty",),
		'product_qty' => array('type'=>'double(24,8)', 'label'=>'ProductBackorderQty', 'enabled'=>1, 'visible'=>-1, 'position'=>125, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"calculated backorder total of product qty",),
		'product_stock' => array('type'=>'double(24,8)', 'label'=>'ProductStock', 'enabled'=>1, 'visible'=>-1, 'position'=>125, 'notnull'=>1, 'default'=>'0', 'isameasure'=>'1', 'comment'=>"Stock of product",),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>200, 'notnull'=>1, 'visible'=>5, 'index'=>1, 'default'=>0, 'arrayofkeyval'=>array('0'=>'BackOrderNotShippable', '1'=>'BackOrderShippable', '2'=>'PartlyShippable', '3'=>'NotFullyShippable'),'arrayofkeyvalshort'=>array('0'=>'BackOrderNotShippableShort', '1'=>'BackOrderShippableShort', '2'=>'PartlyShippableShort', '3'=>'NotFullyShippableShort'),),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		'entity' => array('type'=>'sellist:entity:label:rowid', 'label'=>'Entity', 'enabled'=>1, 'visible'=>-2, 'notnull'=> 1, 'default'=>1, 'index'=>1, 'position'=>502),
	);
	public $getBackorderFields=array(
		'fk_product' => array('name'=>'cd.fk_product'),
		'fk_commandedet' => array('name'=>'cd.rowid as fk_commandedet'),
		'fk_commande' => array('name'=>'cd.fk_commande'),
		'commande_statut' => array('name'=>'c.fk_statut as commande_statut'),
		'ref_client' => array('name'=>'c.ref_client'),
		'fk_soc' => array('name'=>'c.fk_soc'),
		'fk_projet' => array('name'=>'c.fk_projet'),
		'fk_user_author' => array('name'=>'c.fk_user_author'),
		'town' => array('name'=>'s.town'),
		'zip' => array('name'=>'s.zip'),
		'fk_departement' => array('name'=>'s.fk_departement'),
		'fk_pays' => array('name'=>'s.fk_pays'),
		'date_creation' => array('name'=>'c.date_creation'),
		'date_livraison' => array('name'=>'c.date_livraison'),
		'price' => array('name'=>'cd.price'),
		'qty' => array('name'=>'cd.qty'),
		'eqty' => array('name'=>'SUM(ed.qty) as eqty'),
		'entity' => array('name'=>'c.entity'),
		'amount' => array('type'=>'double(24,8)')
	);
	public $fk_product;
	public $rowid;
	public $fk_commandedet;
	public $fk_commande;
	public $commande_statut;
	public $ref_client;
	public $fk_soc;
	public $fk_projet;
	public $fk_user_author;
	public $town;
	public $zip;
	public $state;
	public $fk_pays;
	public $date_creation;
	public $date_livraison;
	public $price;
	public $qty;
	public $eqty;
	public $line_amount;
	public $order_amount;
	public $line_qty;
	public $order_qty;
	public $entity;
	public $product_qty;
	public $product_stock;
	public $tms;
	// END MODULEBUILDER PROPERTIES

	public $entityElement;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		dol_include_once('/backorder/core/modules/modBackOrder.class.php');

		$this->db = $db;
		$this->entityElement = 'commande';

		$module = new modBackOrder($this->db);

		$moduleEnabled = '';

		if ((int) DOL_VERSION < 20) {
			$this->key_ship_line_order_line = 'fk_origin_line';
		}

		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (($key == 'fk_projet' || $key == 'fk_user_author') && isset($val['enabled'])) {
				if ($key == 'fk_projet') $moduleEnabled = 'project';
				if ($key == 'fk_user_author') $moduleEnabled = 'user';
				if (!isset($conf->global->BACKORDER_INIT_VERSION) || !empty($conf->global->BACKORDER_INIT_VERSION) && $conf->global->BACKORDER_INIT_VERSION != $module->version) {
					// no new fields in this version $val['enabled'] = 0;
				}
				if (!function_exists('isModEnabled') && $moduleEnabled && empty($conf->modules[$moduleEnabled])) {
					$val['enabled'] = 0;
				} else {
					$val['enabled'] = dol_eval($val['enabled'], 1);
				}
			}
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}
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

		$object = new static($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && ! empty($object->table_element_line)) $object->fetchLines();

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);


		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (! empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
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

		if (! $error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
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
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines=array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string		$sortorder		Sort Order
	 * @param  string		$sortfield    Sort field
	 * @param  int			$limit        limit
	 * @param  int			$offset       Offset
	 * @param  array|string	$filter       Filter as an Universal Search string.
	 * 									 Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string		$filtermode   No more used
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = null, $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records=array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';
		if ($this->ismultientitymanaged) $sql .= ' WHERE t.entity IN ('.getEntity($this->entityElement).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		if ((int) DOL_VERSION >= 20) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
			if ($errormessage) {
				$this->errors[] = $errormessage;
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				return -1;
			}
		} else {
			// backward compatibility
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key=='t.rowid') {
						$sqlwhere[] = $key . '='. $value;
					} elseif (strpos($key, 'date') !== false) {
						$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
					} elseif ($key=='customsql') {
						$sqlwhere[] = $value;
					} else {
						$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
					}
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= ' AND (' . implode(' '.$filtermode.' ', $sqlwhere).')';
			}
		}
		

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .=  ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if (!empty($limit)) {
				$num = min($limit, $num);
			}
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$record = new static($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->error = 'Error ' . $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * set back order shippable status
	 *
	 * @param  User $user      User that modifies
	 * @param  int $productId      product rowid, if not set, set status field for current backorder record
	 *
	 * @return int             <0 if KO, >0 if OK
	 */
	public function setStatus($user, $productId)
	{
		$result = 0;

		if ($productId > 0) {
			$productQty = 0;

			if ((int) DOL_VERSION >= 20) {
				$filter = '(fk_product:=:'.$productId.')';
			} else {
				$filter = array('customsql' => 'fk_product = ' . ((int) $productId));
			}

			$records = $this->fetchAll('', '', 0, 0, $filter);
			// get total backorder qty for product
			if (is_array($records) && count($records) > 0) {
				foreach ($records as $record) {
					$productQty += $record->line_qty;
				}
			}
			if (is_array($records) && count($records) > 0) {
				$product = new Product($this->db);
				$result = $product->fetch($productId);
				if ($result > 0) $result = $product->load_stock('novirtual');
				if ($result > 0) {
					foreach ($records as $record) {
						$currentStatus = $record->status;
							$productStock = $product->stock_reel;
						if ($productStock <= 0) {
							$record->status = self::STATUS_NOT_SHIPPABLE;
						} elseif ($productStock >= $productQty) {
							$record->status = self::STATUS_SHIPPABLE;
						} else {
							if ($productStock >= $record->line_qty) {
								$record->status = self::STATUS_PARTLY_SHIPPABLE;
							} else {
								$record->status = self::STATUS_NOT_FULLY_SHIPPABLE;
							}
						}
						if ($currentStatus != $record->status || $productQty != $record->product_qty || $productStock != $record->product_stock) {
							$record->product_qty = $productQty;
							$record->product_stock = $productStock;
							$result = $record->update($user);
							if ($result < 0) {
								$this->error = $record->error;
								$this->errors[] = $this->error;
								return $result;
							}
						}
					}
				} elseif ($result < 0) {
					$this->error = $product->error;
					$this->errors[] = $this->error;
				}
			} elseif ($records < 0) {
				return $records;
			}
		}
		return $result;
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
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			$this->errors[] = $this->error;
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}

	/**
	 * Get backorder id for order line
	 *
	 * @param int    $fk_commande   Id of order
	 * @param int    $fk_commandedet   Id of order line
	 *
	 * @return object         <0 if KO, 0 if not found, id if object found
	 */
	public function getBackOrderId($fk_commande, $fk_commandedet)
	{
		$countRecords = 0;

		if ((int) DOL_VERSION >= 20) {
			$filter = '((fk_commande:=:'.$fk_commande.') AND (fk_commandedet:=:'.$fk_commandedet.'))';
		} else {
			$filter = array('customsql' => 'fk_commande = ' . ((int) $fk_commande) . ' AND fk_commandedet = ' . ((int) $fk_commandedet));
		}

		$records = $this->fetchAll('', '', 0, 0, $filter);
		if (is_array($records)) {
			$countRecords = count($records);
			if ($countRecords == 1) {
				foreach ($records as $key => $record) {
					$result = $record->id;
					if ($result > 0) {
						return $result;
					}
				}
			}
		} else {
			return $records;
		}
	}

	/**
	 * Get backorder qty of order
	 *
	 * @param int    $fk_commande   Id of order
	 * @param int    $fk_commandedet   Id of order line
	 *
	 * @return object         <0 if KO, 0 if not found, qty if found
	 */
	public function getBackOrderQty($fk_commande, $fk_commandedet = null)
	{
		$qty = 0;

		if ((int) DOL_VERSION >= 20) {
			$filter = '(fk_commande:=:'.$fk_commande.')';
			if (isset($fk_commandedet)) {
				$filter = '('.$filter.' AND (fk_commandedet:=:'.(int) $fk_commandedet.'))';
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
			foreach ($records as $record) {
				$qty += $record->line_qty;
			}
			return $qty;
		} else {
			return $records;
		}
	}

	/**
	 * Get and set backorder values
	 *
	 * @param bool   $mode					mode read, update or delete
	 * @param int    $fk_commande			Id of order
	 * @param int    $fk_commandedet		Id of order line
	 * @param int    $fk_expedition_delete	Id of deleted shipment
	 * @param float  $delete_qty			deleted qty from trigger.
	 *
	 * @return int         <0 if KO, 0 if not found, 1 if object fetched, > 1 if total ammount fetched
	 */
	public function getBackorder($mode, $fk_commande = null, $fk_commandedet = null, $fk_expedition_delete = null, $delete_qty = null)
	{
		global $user;

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
				if ($countRecords && ($mode == 'delete' || $mode == 'validate')) {
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

		if ($mode != 'delete' && (empty($countRecords) || $mode == 'update' || $mode == 'validate')) {
			$this->line_amount = 0;
			$this->line_qty = 0;
			$backorderAmount = array();
			$backorderQty = array();
			$sql = 'SELECT ';
			$sql.= $this->getBackorderFieldList();
			$sql.= " FROM ".MAIN_DB_PREFIX.'commande'." as c";
			$sql.= $this->getBackorderFieldFrom($fk_expedition_delete);
			if ($this->ismultientitymanaged == 1) $sql.= " WHERE c.entity IN (".getEntity($this->entityElement).")";
			else $sql.= " WHERE 1 = 1";
			if ($fk_commande > 0) {
				$sql.= " AND c.rowid = " . ((int) $fk_commande);
			} else {
				$sql.= ' AND c.fk_statut < '.Commande::STATUS_CLOSED . ' AND c.fk_statut != '.Commande::STATUS_CANCELED;
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

						$obj = new BackOrder($this->db);
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
						if ($obj->commande_statut >= Commande::STATUS_CLOSED) {
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
								};
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

				// set order backorder amount and shippable status
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
									if ($result > 0) $result = $record->setStatus($user, $record->fk_product);
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
							if ($result > 0) $result = $this->setStatus($user, $this->fk_product);
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
	 * @param int    $fk_expedition_delete   Id of deleted shipment
	 * @return string sql from part
	 */
	public function getBackorderFieldFrom($fk_expedition_delete = null)
	{
		global $conf;

		$sql = ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet cd ON cd.fk_commande = c.rowid AND c.fk_statut > '.Commande::STATUS_DRAFT;
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON c.fk_soc = s.rowid';
		if (!empty($conf->global->BACKORDER_CUSTOMER_ORDER_NONSHIP)) {
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."element_element ee ON ee.fk_source = c.rowid AND ee.targettype = 'shipping'";
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'expedition e ON ee.fk_target = e.rowid AND e.fk_statut > 0';
			if ($fk_expedition_delete) $sql.= ' AND e.rowid <> ' . ((int) $fk_expedition_delete);
		} else {
			$sql.= ' INNER JOIN '.MAIN_DB_PREFIX."element_element ee ON ee.fk_source = c.rowid AND ee.targettype = 'shipping'";
			$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'expedition e ON ee.fk_target = e.rowid AND e.fk_statut > 0';
			if ($fk_expedition_delete) $sql.= ' AND e.rowid <> ' . ((int) $fk_expedition_delete);
		}
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet ed ON ed.fk_expedition = e.rowid AND ed.'.$this->key_ship_line_order_line.' = cd.rowid';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
		if (empty($conf->global->BACKORDER_SERVICES)) $sql.= ' AND p.fk_product_type = 0';
		else $sql.= ' AND p.fk_product_type IN (0,1)'; // to avoid special lines (ie subtotal, titles...)
		return $sql;
	}

	/**
	 * Function public getBackorderFieldList()
	 * @return string
	 */
	public function getBackorderFieldList()
	{
		$fields = array();
		foreach ($this->getBackorderFields as $key=>$val) {
			if (!empty($val['name'])) $fields[] = $val['name'];
		}
		return implode(',', $fields);
	}

	/**
	 * Function to concat keys of fields
	 *
	 * @return string
	 */
	public function getBackorderFieldListGroupBy()
	{
		$fields = array();
		foreach ($this->getBackorderFields as $key=>$val) {
			if (isset($val['name'])) {
				$splitField = preg_split("/[\s]+/", $val['name']); // no 'as' allowed in group by
				if ($splitField) {
					$field = $splitField[0];
				} else {
					$field = $val['name'];
				}
				$isGroupField = preg_match('/SUM|COUNT|AVERAGE/i', $field);
				if (! empty($field) && ! $isGroupField) $fields[] = $field;
			}
		}
		return implode(',', $fields);
	}

	/**
	 * getAmount
	 * @param float $price price
	 * @param float $orderQty ordered qty
	 * @param float $shippedQty shipped qty
	 * @return float
	 */
	public static function getAmount($price, $orderQty, $shippedQty)
	{
		//clean parameters
		$price+=0;
		$orderQty+=0;
		$shippedQty+=0;

		return ($orderQty - $shippedQty) * $price;
	}

	/**
	 * getQty
	 * @param float $orderQty ordered qty
	 * @param float $shippedQty shipped qty
	 * @return float
	 */
	public static function getQty($orderQty, $shippedQty)
	{
		//clean parameters
		$orderQty+=0;
		$shippedQty+=0;

		return $orderQty - $shippedQty;
	}

	/**
	 * deleteAll
	 *
	 * @return > 0 OK < 0 NOK
	 */
	public function deleteAll()
	{
		$this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
		if ($this->ismultientitymanaged) $sql .= ' WHERE entity IN ('.getEntity($this->entityElement).')';

		dol_syslog(get_class($this)."::deleteAll", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	object	$object						Backorder object
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
		$orderline = new OrderLine($this->db);
		$orderline->fetch($object->fk_commandedet);

		$label = '<u>' . $langs->trans("BackOrder") . '</u>';
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('ShortLabel') . ':</b> ' . $orderline->label;
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('Description') . ':</b> ' . $orderline->desc;
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
		if (!empty($orderline->product_label)) {
			$result .= $orderline->product_label;
		} elseif (!empty($orderline->desc)) {
			$result .= $orderline->desc;
		} elseif (!empty($orderline->label)) {
			$result .= $orderline->label;
		} elseif (!empty($orderline->product_desc)) {
			$result .= $orderline->product_desc;
		} else {
			$result .= $langs->trans('product');
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
	 * @Overide
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  object  $object         database row object
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $showsize       Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputField($val, $key, $object, $moreparam = '', $keysuffix = '', $keyprefix = '', $showsize = 0)
	{
		global $conf,$langs,$form;

		if (! is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form=new Form($this->db);
		}

		$value = $object->$key;
		$type  = isset($val['type']) ? $val['type'] : '';
		$size  = isset($val['css']) ? $val['css'] : 0;
		$reg = array();

		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg)) {
			$type = 'varchar';		// convert varchar(xx) int varchar
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $type)) $type = 'varchar';		// convert varchar(xx) int varchar
		if (preg_match('/double/', $type)) $type = 'double';		// convert double(xx) into double
		if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) $type='select';
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) $type='link';

		$param = isset($val['param']) ? $val['param'] : array();
		if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) $param['options'] = $val['arrayofkeyval'];
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type='link';
			$param['options']=array($reg[1].':'.$reg[2]=>$reg[1].':'.$reg[2]);
		} elseif (preg_match('/^sellist:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2] => 'N');
			$type = 'sellist';
		}
		$hidden=(($val['visible'] == 0) ? 1 : 0);			// If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) return '';

		if (empty($showsize)) {
			if ($type == 'date') {
				$showsize = 'minwidth100imp';
			} elseif ($type == 'datetime') {
				$showsize = 'minwidth200imp';
			} elseif (in_array($type, array('int','double','price'))) {
				$showsize = 'maxwidth75';
			} elseif ($type == 'url') {
				$showsize='minwidth400';
			} elseif ($type == 'boolean') {
				$showsize='';
			} else {
				if (is_numeric($size) && round($size) < 12) {
					$showsize = 'minwidth100';
				} elseif (is_numeric($size) && round($size) <= 48) {
					$showsize = 'minwidth200';
				} else {
					$showsize = 'minwidth400';
				}
			}
		}

		// Format output value differently according to properties of field
		if ($key == 'fk_commandedet' && method_exists($this, 'getNomUrl')) $value=$this->getNomUrl($object, 'nolink', 0, '', 1);
		elseif ($key == 'fk_product') {
			if (!empty($value)) {
				if (!empty($object->fk_product)) {
					$value = $this->getOriginUrl($object->fk_product, 'product');
				}
			} else {
				$value = $langs->trans('FreeLine');
			}
		} elseif ($key == 'fk_commande') {
			if (!empty($value)) {
				if (isset($object->fk_commande)) {
					$value = $this->getOriginUrl($object->fk_commande, 'commande');
				}
			} else {
				$value = '';
			}
		} elseif ($key == 'fk_user_modif') {
			if (!empty($value)) {
				if (isset($object->fk_user_modif)) {
					$value = $this->getOriginUrl($object->fk_user_modif, 'user');
				}
			} else {
				$value = '';
			}
		} elseif ($key == 'fk_departement' && $value > 0) {
			$value = getState($object->fk_departement);
		} elseif ($key == 'fk_pays') {
			$tmparray = getCountry($object->fk_pays, 'all');
			$value = $tmparray['label'];
		} elseif ($key =='amount') {
			$value = price($this->getAmount($object->price, $object->qty, $object->eqty));
		} elseif ($key == 'status' && method_exists($this, 'getLibStatut')) $value=$this->getLibStatut(3);
		elseif ($type == 'double') {
			if (!empty($value)) {
				$value=price($value);
			} else {
				$value=0;
			}
		} elseif ($type == 'boolean') {
			$checked='';
			if (!empty($value)) {
				$checked=' checked ';
			}
			$value='<input type="checkbox" '.$checked.' '.($moreparam?$moreparam:'').' readonly disabled>';
		} elseif ($type == 'mail') {
			$value=dol_print_email($value, 0, 0, 0, 64, 1, 1);
		} elseif ($type == 'url') {
			$value=dol_print_url($value, '_blank', 32, 1);
		} elseif ($type == 'phone') {
			$value=dol_print_phone($value, '', 0, 0, '', '&nbsp;', 1);
		} elseif ($type == 'price') {
			$value=price($value, 0, $langs, 0, 0, -1, $conf->currency);
		} elseif ($type == 'select') {
			$value=$param['options'][$value];
		} elseif ($type == 'sellist') {
			$param_list=array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey="rowid";
			$keyList='rowid';

			if (count($InfoFieldList)>=3) {
				$selectkey = $InfoFieldList[2];
				$keyList=$InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .=', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT '.$keyList;
			$sql.= ' FROM '.MAIN_DB_PREFIX .$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra')!==false) {
				$sql.= ' as main';
			}
			if ($selectkey=='rowid' && empty($value)) {
				$sql.= " WHERE ".$selectkey."=0";
			} elseif ($selectkey=='rowid') {
				$sql.= " WHERE ".$selectkey."=".$this->db->escape($value);
			} else {
				$sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value='';	// value was used, so now we reste it to use it to build final output

				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|', $InfoFieldList[1]);

				if (is_array($fields_label) && count($fields_label)>1) {
					foreach ($fields_label as $field_toshow) {
						$translabel='';
						if (!empty($obj->$field_toshow)) {
							$translabel=$langs->trans($obj->$field_toshow);
						}
						if ($translabel!=$field_toshow) {
							$value.=dol_trunc($translabel, 18).' ';
						} else {
							$value.=$obj->$field_toshow.' ';
						}
					}
				} else {
					$translabel='';
					if (!empty($obj->{$InfoFieldList[1]})) {
						$translabel=$langs->trans($obj->{$InfoFieldList[1]});
					}
					if ($translabel!=$obj->{$InfoFieldList[1]}) {
						$value=dol_trunc($translabel, 18);
					} else {
						$value=$obj->{$InfoFieldList[1]};
					}
				}
			} else dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
		} elseif ($type == 'radio') {
			$value=$param['options'][$value];
		} elseif ($type == 'checkbox') {
			$value_arr=explode(',', $value);
			$value='';
			if (is_array($value_arr)) {
				foreach ($value_arr as $keyval=>$valueval) {
					$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$param['options'][$valueval].'</li>';
				}
			}
			$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
		} elseif ($type == 'chkbxlst') {
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT ' . $keyList;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this) . ':showOutputField:$type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output
				$toprint=array();
				while ( $obj = $this->db->fetch_object($resql) ) {
					// Several field into label (eq table:code|libelle:rowid)
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ($fields_label as $field_toshow) {
								$translabel = '';
								if (! empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
								} else {
									$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->$field_toshow.'</li>';
								}
							}
						} else {
							$translabel = '';
							if (! empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.dol_trunc($translabel, 18).'</li>';
							} else {
								$toprint[]='<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #aaa">'.$obj->{$InfoFieldList[1]}.'</li>';
							}
						}
					}
				}
				$value='<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
			} else {
				dol_syslog(get_class($this) . '::showOutputField error ' . $this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'link') {
			$out='';

			// only if something to display (perf)
			if ($value) {
				$param_list=array_keys($param['options']);				// $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname=$InfoFieldList[0];
				$classpath=$InfoFieldList[1];
				if (! empty($classpath)) {
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname)) {
						$object = new $classname($this->db);
						$object->fetch($value);
						$value=$object->getNomUrl(3);
					}
				} else {
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			}
		} elseif ($type == 'text' || $type == 'html') {
			$value=dol_htmlentitiesbr($value);
		} elseif ($type == 'password') {
			$value=preg_replace('/./i', '*', $value);
		} elseif ($type == 'array') {
			$value = implode('<br>', $value);
		}

		//print $type.'-'.$size;
		$out=$value;

		return $out;
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
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				$origin = new Commande($this->db);
				break;
			case 'shipping':
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$origin = new Expedition($this->db);
				break;
			case 'order_supplier':
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$origin = new CommandeFournisseur($this->db);
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
	* Return Url link of origin object
	*
	* @param int $fk_origin  Id origin
	* @param int $origintype Type origin
	*
	* @return string
	*/
	public function getOriginUrl($fk_origin, $origintype)
	{
		$origin=$this->getOrigin($origintype);

		if (empty($origin) || ! is_object($origin)) return '';

		if ($origin->fetch($fk_origin) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int	$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@param      int	$type       0=Sell, 1=Buy, 2=Batch Number management
	 *	@return     string      	Label of status
	 */
	public function getLibStatut($mode = 0, $type = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if (isset($this->product_qty) && isset($this->product_stock)) {
			$this->labelStatus[self::STATUS_NOT_SHIPPABLE] = $langs->trans('BackOrderNotShippable', $this->product_qty, $this->product_stock);
			$this->labelStatus[self::STATUS_SHIPPABLE] = $langs->trans('BackOrderShippable', $this->product_qty, $this->product_stock);
			$this->labelStatus[self::STATUS_PARTLY_SHIPPABLE] = $langs->trans('PartlyShippable', $this->product_qty, $this->product_stock);
			$this->labelStatus[self::STATUS_NOT_FULLY_SHIPPABLE] = $langs->trans('NotFullyShippable', $this->product_qty, $this->product_stock);
			$this->labelStatusShort[self::STATUS_NOT_SHIPPABLE] = $langs->trans('BackOrderNotShippable', $this->product_qty, $this->product_stockk);
			$this->labelStatusShort[self::STATUS_SHIPPABLE] = $langs->trans('BackOrderShippable', $this->product_qty, $this->product_stock);
			$this->labelStatusShort[self::STATUS_PARTLY_SHIPPABLE] = $langs->trans('PartlyShippable', $this->product_qty, $this->product_stock);
			$this->labelStatusShort[self::STATUS_NOT_FULLY_SHIPPABLE] = $langs->trans('NotFullyShippable', $this->product_qty, $this->product_stock);
		} else {
			$this->labelStatus[self::STATUS_NOT_SHIPPABLE] = $langs->trans('BackOrderNotShippableShort');
			$this->labelStatus[self::STATUS_SHIPPABLE] = $langs->trans('BackOrderShippableShort');
			$this->labelStatus[self::STATUS_PARTLY_SHIPPABLE] = $langs->trans('PartlyShippableShort');
			$this->labelStatus[self::STATUS_NOT_FULLY_SHIPPABLE] = $langs->trans('NotFullyShippableShort');
			$this->labelStatusShort[self::STATUS_NOT_SHIPPABLE] = $langs->trans('BackOrderNotShippableShort');
			$this->labelStatusShort[self::STATUS_SHIPPABLE] = $langs->trans('BackOrderShippableShort');
			$this->labelStatusShort[self::STATUS_PARTLY_SHIPPABLE] = $langs->trans('PartlyShippableShort');
			$this->labelStatusShort[self::STATUS_NOT_FULLY_SHIPPABLE] = $langs->trans('NotFullyShippableShort');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_SHIPPABLE) $statusType = 'status4';
		elseif ($status == self::STATUS_NOT_SHIPPABLE) $statusType = 'status8';
		elseif ($status == self::STATUS_PARTLY_SHIPPABLE) $statusType = 'status1';
		elseif ($status == self::STATUS_NOT_FULLY_SHIPPABLE) $statusType = 'status6';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
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
				$this->output = $langs->trans("CustomerBackordersGenerated", $result);
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
