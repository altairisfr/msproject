<?php
/* Copyright (C) 2016 altairis <contact@altairis.fr>
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
 *  \file       htdocs/core/triggers/interface_90_all_Altapdf.class.php
 *  \ingroup    prices
 *  \brief      Fichier de définition des triggers du module Altapdf
 *  \remarks    Triggers pris en charge :
 *              - PRODUCT_DELETE : suppression des prix du produit supprimé
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for Altapdf module
 */


class InterfaceAltapdf extends DolibarrTriggers
{
    var $db;

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "Altapdf";
        $this->description = "Fichier de définition des triggers du module Altapdf";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }


    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	public function runTrigger($action,$object,$user,$langs,$conf)
	{

 		if ( ! empty($object->context['extrafieldaddupdate']) && ($action == 'ORDER_MODIFY' || $action =='PROPAL_MODIFY' || $action == 'BILL_MODIFY' || $action == 'SHIPMENT_MODIFY' || $action == 'ORDER_SUPPLIER_MODIFY'))
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            return $this->update_extras($action,$object,$user,$langs,$conf);
        }

 		return 0;
    }

    /** Manages custom address in shipment PDF
     * @param   string        $action
     * @param   CommonObject  $object
     * @param   User          $user
     * @param   Translate     $langs
     * @param   Conf          $conf
     * @return  int
     */
    private function update_extras($action,$object,$user,$langs,$conf)
    {
		if (!empty($_POST['options_manual_shipping_site']))
		{
			$object->array_options['options_shipping_site'] = '';
            $object->array_options['options_shipping_site_supplier_order'] = '';
			return $object->insertExtraFields();
		}

		elseif (!empty($_POST['options_shipping_site']))
		{
			$object->array_options['options_manual_shipping_site'] = '';
			return $object->insertExtraFields();
		}

        elseif (!empty($_POST['options_shipping_site_supplier_order']))
		{
			$object->array_options['options_manual_shipping_site'] = '';
			return $object->insertExtraFields();
		}
        return 0;
	}
}
