<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**     \defgroup   warranty     Module Warranty
 *      \brief      Module to manage warranty
 *      \file       htdocs/includes/modules/modWarranty.class.php
 *      \ingroup    warranty
 *      \brief      Description and activation file for module Warranty
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 	Class to describe module Warranty
 */
class modAltapdf extends DolibarrModules
{
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 140025;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'altapdf';

		$this->editor_name = 'Altairis';
		$this->editor_url = 'http://altairis.fr';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "Altairis";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Full altairis pdf model";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '2026.06';
		// Url to the file with your last numberversion of this module
		$this->url_last_version = 'https://dev.altairis.net/modver/'.strtolower($this->name).'.txt';
		/* add a / at the beginning of this line to uncomment this for untagged versions
		if (function_exists('exec')) {
			$output_code = 0;
			$output = array();
			exec('cd '.__DIR__. ' && git describe --tags 2> /dev/null', $output, $output_code);
			$dev_version = isset($output[0]) ? $output[0] : '';
			if (!empty($dev_version)) {
				$this->version = $dev_version;
			}
		}
		//*/
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 2;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='altapdf@altapdf';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array("setup.php@altapdf");//array("admin.php@altapdf");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();				// List of modules id to disable if this one is disabled
		$this->phpmin = array(7, 4);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(18, 0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("altapdf@altapdf");

		// Constants
		$this->const=array(
			1 => array('ALTAPDF_PREFIX_PDF', 'int', '2', 'Sélection du préfix PDF', 1),
		);


		// New pages on tabs
		$this->tabs = array();

		// hooks
		$this->module_parts = array(
            'models' => 1,
            'triggers' => 1,
            'hooks'  => array(
                'invoicecard'
                ,'invoicesuppliercard'
                ,'propalcard'
                ,'supplier_proposalcard'
                ,'ordercard'
                ,'ordersuppliercard'
                ,'odtgeneration'
                ,'pdfgeneration'
                ,'orderstoinvoice'
                ,'orderstoinvoicesupplier'
                ,'admin'
                ,'invoicereccard'
                ,'consumptionthirdparty'
            	,'ordershipmentcard'
            	,'expeditioncard'
				,'deliverycard'
				,'paiementcard'
				,'ajaxonlinesign'
				,'orderlist'
				,'invoicelist'
				,'propallist'
				,'productreturnlist'
				,'supplierorderlist'
				,'shipmentlist'
            )
		);

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r = 0;
	    // top menu entry
	}

	/**
     *	Function called when module is enabled.
     *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *	It also creates data directories.
     *
     *	@return     int             1 if OK, 0 if KO
     */
	function init($options = '')
  	{
		global $conf, $db, $langs;


		$this->load_tables();

        $sql = array();

		// Create extrafields during init.
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		$extraFields = new ExtraFields($this->db);

		$extraFields->addExtraField('shipping_address', 'ShippingAddress', 'text', 100, '2000', 'societe', 0, 0, NULL, 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$shipping = array(
			'options' => array('Client'=>'ClientAddress',
			'TakenAddress'=>'TakenAddress')
		);
		$extraFields->addExtraField('shipping_site', 'ShippingSite', 'select', 10, '', 'propal', 0, 0, '', $shipping, 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('shipping_site', 'ShippingSite', 'select', 10, '', 'commande', 0, 0, '', $shipping, 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('shipping_site', 'ShippingSite', 'select', 10, '', 'facture', 0, 0, '', $shipping, 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('manual_shipping_site', 'ManualShippingSite', 'text', 11, '2000', 'propal', 0, 0, '', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', 1, '', 1, '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('manual_shipping_site', 'ManualShippingSite', 'text', 11, '2000', 'commande', 0, 0, '', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('manual_shipping_site', 'ManualShippingSite', 'text', 11, '2000', 'facture', 0, 0, '', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');
		$extraFields->addExtraField('manual_shipping_site', 'ManualShippingSite', 'text', 11, '2000', 'commande_fournisseur', 0, 0, '', 'a:1:{s:7:\"options\";a:1:{s:0:\"\";N;}}', 1, '', '1', '', '', $conf->entity, 'altapdf@altapdf');

		dolibarr_set_const($this->db, 'MAIL_MASS_ACTION_ADD_LAST_IF_MAIN_DOC_NOT_FOUND', '1', 'chaine', 0, '', $conf->entity);

        return $this->_init($sql);
  	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *	@return     int             1 if OK, 0 if KO
 	 */
	function remove($options = '')
	{
    	$sql = array();

        //$this->_load_tables('/notes/sql/disable/');

    	return $this->_remove($sql);
  	}


	/**
	 * 	Create tables and keys required by module
	 * 	Files mymodule.sql and mymodule.key.sql with create table and create keys
	 * 	commands must be stored in directory /mymodule/sql/
	 * 	This function is called by this->init.
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
  	function load_tables()
	{
		return $this->_load_tables('/altapdf/sql/');
	}
}
