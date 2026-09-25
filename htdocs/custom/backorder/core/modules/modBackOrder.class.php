<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\defgroup   backorder     Module BackOrder
 *  \brief      BackOrder module descriptor.
 *
 *  \file       htdocs/backorder/core/modules/modBackOrder.class.php
 *  \ingroup    backorder
 *  \brief      Description and activation file for module BackOrder
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module BackOrder
 */
class modBackOrder extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs,$conf;

		$this->db = $db;

		// Id for module
		$this->numero = 202010;
		// Key text used to identify module
		$this->rights_class = 'backorder';

		// Family
		$this->family = "other";
		// Module position in the family
		$this->module_position = '90';

		// Module label
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description
		$this->description = "BackOrderDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "BackOrderDescription (Long)";

		$this->editor_name = 'Z-Application';
		$this->editor_url = 'https://www.z-application.com';

		// Version of module
		$this->version = '1.0.23';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		$this->picto='backorder.png@backorder';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /backorder/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /backorder/core/modules/barcode)
		// for specific css file (eg: /backorder/css/backorder.css.php)
		$this->module_parts = array(
									'triggers' => 1,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0,                                    	// Set this to 1 if module has its own login method file (core/login)
									'substitutions' => 1,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
									'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
									//'css' => array('/backorder/css/backorder.css.php'),	// Set this to relative path of css file if module has its own css file
									//'js' => array('/backorder/js/backorder.js.php'),	// Set this to relative path of js file if module must load a js on all pages
									'hooks' => array('data'=>array('orderlist', 'ordercard', 'ordersindex', 'supplierorderlist', 'ordersuppliercard', 'orderssuppliersindex'), 'entity'=>'0'), 	// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
									'moduleforexternal' => 0							// Set this to 1 if feature of module are opened to external users
								);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/backorder/temp","/backorder/subdir");
		$this->dirs = array("/backorder/temp");

		// Config pages. Put here list of php page, stored into backorder/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@backorder");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module class names to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("backorder@backorder");
		$this->phpmin = array(5,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(13,0);	// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'BackOrderWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (! isset($conf->backorder) || ! isset($conf->backorder->enabled)) {
			$conf->backorder=new stdClass();
			$conf->backorder->enabled=0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@backorder:$user->rights->backorder->read:/backorder/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@backorder:$user->rights->othermodule->read:/backorder/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		/* Example:
		$this->dictionaries=array(
			'langs'=>'mylangfile@backorder',
			'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
			'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
			'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
			'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
			'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
			'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
			'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
			'tabcond'=>array($conf->backorder->enabled,$conf->backorder->enabled,$conf->backorder->enabled)												// Condition to show each dictionary
		);
		*/


		// Boxes/Widgets
		// Add here list of php file(s) stored in backorder/core/boxes that contains class to show a widget.
		$this->boxes = array(
			//0=>array('file'=>'backorderwidget1.php@backorder','note'=>'Widget provided by BackOrder','enabledbydefaulton'=>'Home'),
			//1=>array('file'=>'backorderwidget2.php@backorder','note'=>'Widget provided by BackOrder'),
			//2=>array('file'=>'backorderwidget3.php@backorder','note'=>'Widget provided by BackOrder')
		);


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/*$this->cronjobs = array(
			0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/backorder/class/backorder.class.php', 'objectname'=>'BackOrder', 'method'=>'doScheduledJob', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->backorder->enabled')
		);*/
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->backorder->enabled'),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->backorder->enabled')
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'ReadBackorders';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->backorder->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->backorder->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'ReadSupplierBackorders';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'supplier';				// In php code, permission will be checked by test if ($user->rights->backorder->level1->level2)
		$this->rights[$r][5] = 'read';				    // In php code, permission will be checked by test if ($user->rights->backorder->level1->level2)


		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		/* BEGIN MODULEBUILDER LEFTMENU BACKORDER */
		$this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=orders',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		'type'=>'left',			                // This is a Left menu entry
		'titre'=>'BackOrderList',
		'mainmenu'=>'commercial',
		'leftmenu'=>'backorder_list',
		'url'=>'/backorder/backorder_list.php',
		'langs'=>'backorder@backorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>1000+$r,
		'enabled'=>'getDolGlobalInt("BACKORDER_CUSTOMER_ORDER")',  // Define condition to show or hide menu entry. Use '$conf->backorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		'perms'=>'$user->rights->backorder->read',			                // Use 'perms'=>'$user->rights->backorder->level1->level2' if you want your menu with a permission rules
		'target'=>'',
		'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		$this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=orders_suppliers',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		'type'=>'left',			                // This is a Left menu entry
		'titre'=>'BackOrderList',
		'mainmenu'=>'commercial',
		'leftmenu'=>'backorder_supplier_list',
		'url'=>'/backorder/backorder_supplier_list.php',
		'langs'=>'backorder@backorder',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		'position'=>1000+$r,
		'enabled'=>'getDolGlobalInt("BACKORDER_SUPPLIER_ORDER")',  // Define condition to show or hide menu entry. Use '$conf->backorder->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		'perms'=>'$user->rights->backorder->supplier->read',			                // Use 'perms'=>'$user->rights->backorder->level1->level2' if you want your menu with a permission rules
		'target'=>'',
		'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER LEFTMENU BACKORDER */


		// Exports
		$r=1;

		/* BEGIN MODULEBUILDER EXPORT BACKORDER */
		/*
		$langs->load("backorder@backorder");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='BackOrderLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='backorder@backorder';
		$keyforclass = 'BackOrder'; $keyforclassfile='/mymobule/class/backorder.class.php'; $keyforelement='backorder';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='backorder'; $keyforaliasextra='extra'; $keyforelement='backorder';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'backorder as t';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('backorder').')';
		$r++; */
		/* END MODULEBUILDER EXPORT BACKORDER */
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		$result=$this->_load_tables('/backorder/sql/');
		if ($result < 0) return -1; // Do not activate module if not allowed errors found on module SQL queries (the _load_table run sql with run_sql with error allowed parameter to 'default')

		// remove not used module constant
		dolibarr_del_const($this->db, 'MAIN_MODULE_BACKORDER_CSS', -1);
		dolibarr_del_const($this->db, 'MAIN_MODULE_BACKORDER_JS', -1);

		// set default constant on first enable
		if (!isset($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			dolibarr_set_const($this->db, 'BACKORDER_CUSTOMER_ORDER', 1, 'yesno', 0, '', 0);
		}
		if (!isset($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			dolibarr_set_const($this->db, 'BACKORDER_SUPPLIER_ORDER', 1, 'yesno', 0, '', 0);
		}
		if (!isset($conf->global->BACKORDER_INIT_VERSION)) {
			dolibarr_set_const($this->db, 'BACKORDER_INIT_VERSION', $this->version, 'chaine', 0, '', 0);
		}
		if (!empty($conf->global->BACKORDER_INIT_VERSION) && $conf->global->BACKORDER_INIT_VERSION != $this->version) {
			dolibarr_set_const($this->db, 'BACKORDER_INIT_VERSION', $this->version, 'chaine', 0, '', 0);
		}

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
