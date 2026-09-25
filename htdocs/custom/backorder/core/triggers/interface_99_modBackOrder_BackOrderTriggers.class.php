<?php
/* Copyright (C) 2019 Francis Appels <francis.appels@z-application.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modBackOrder_BackOrderTriggers.class.php
 * \ingroup backorder
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modBackOrder_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for BackOrder module
 */
class InterfaceBackOrderTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "BackOrder triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'backorder@backorder';
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->backorder->enabled)) return 0; // Module not active, we do nothing

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		switch ($action) {
			// Users
			//case 'USER_CREATE':
			//case 'USER_MODIFY':
			//case 'USER_NEW_PASSWORD':
			//case 'USER_ENABLEDISABLE':
			//case 'USER_DELETE':
			//case 'USER_SETINGROUP':
			//case 'USER_REMOVEFROMGROUP':

			//case 'USER_LOGIN':
			//case 'USER_LOGIN_FAILED':
			//case 'USER_LOGOUT':
			//case 'USER_UPDATE_SESSION':      // Warning: To increase performances, this action is triggered only if constant MAIN_ACTIVATE_UPDATESESSIONTRIGGER is set to 1.

			// Actions
			//case 'ACTION_MODIFY':
			//case 'ACTION_CREATE':
			//case 'ACTION_DELETE':

			// Groups
			//case 'GROUP_CREATE':
			//case 'GROUP_MODIFY':
			//case 'GROUP_DELETE':

			// Companies
			//case 'COMPANY_CREATE':
			//case 'COMPANY_MODIFY':
			//case 'COMPANY_DELETE':

			// Contacts
			//case 'CONTACT_CREATE':
			//case 'CONTACT_MODIFY':
			//case 'CONTACT_DELETE':
			//case 'CONTACT_ENABLEDISABLE':

			// Products
			//case 'PRODUCT_CREATE':
			//case 'PRODUCT_MODIFY':
			//case 'PRODUCT_DELETE':
			//case 'PRODUCT_PRICE_MODIFY':
			//case 'PRODUCT_SET_MULTILANGS':
			//case 'PRODUCT_DEL_MULTILANGS':

			//Stock mouvement
			case 'STOCK_MOVEMENT':
				// update backorder status (shippable or not)
				/** @var MouvementStock $object */
				$this->setBackOrderStatus($user, $object->product_id);
				break;
			//MYECMDIR
			//case 'MYECMDIR_CREATE':
			//case 'MYECMDIR_MODIFY':
			//case 'MYECMDIR_DELETE':

			// Customer orders
			//case 'ORDER_CREATE':
			case 'ORDER_MODIFY':
			case 'COMMANDE_MODIFY': // link project
				// update project
				$this->setBackOrderAmount($object->id, 'update');
				break;
			case 'ORDER_VALIDATE':
				// set backorder Amount again
				$this->setBackOrderAmount($object->id, 'validate');
				break;
			case 'ORDER_CANCEL':
			case 'ORDER_DELETE':
				$this->setBackOrderAmount($object->id, 'delete');
				break;
			//case 'ORDER_SENTBYMAIL':
			//case 'ORDER_CLASSIFY_BILLED':
			//case 'ORDER_SETDRAFT':
			case 'ORDER_CLOSE':
				// set backorder Amount again
				$this->setBackOrderAmount($object->id, 'delete');
				break;
			case 'ORDER_REOPEN':
				// set backorder Amount again
				$this->setBackOrderAmount($object->id, 'validate');
				break;
			//case 'LINEORDER_INSERT':
			//case 'LINEORDER_UPDATE':
			//case 'LINEORDER_DELETE':

			// Supplier orders
			//case 'ORDER_SUPPLIER_CREATE':
			case 'ORDER_SUPPLIER_MODIFY':
				// update project
				$this->setBackOrderSupplierAmount($object->id, 'update');
				break;
			//case 'ORDER_SUPPLIER_VALIDATE':
			case 'ORDER_SUPPLIER_DELETE':
				$this->setBackOrderSupplierAmount($object->id, 'delete');
				break;
			//case 'ORDER_SUPPLIER_APPROVE':
			case 'ORDER_SUPPLIER_STATUS_APPROVED':
				// re-open from ordered (so not ordered anymore)
				$this->setBackOrderSupplierAmount($object->id, 'delete');
				break;
			case 'ORDER_SUPPLIER_STATUS_RECEIVED_PARTIALLY':
				// re-open from received (so probably again to receive)
				$this->setBackOrderSupplierAmount($object->id);
				break;
			case 'ORDER_SUPPLIER_SUBMIT':
				// Send order to supplier
				$this->setBackOrderSupplierAmount($object->id, 'order');
				break;
			//case 'ORDER_SUPPLIER_REFUSE':
			//case 'ORDER_SUPPLIER_CANCEL':
			//case 'ORDER_SUPPLIER_SENTBYMAIL':
			case 'ORDER_SUPPLIER_DISPATCH':
				$this->setBackOrderSupplierAmount($object->id);
				break;
			case 'ORDER_SUPPLIER_RECEIVE':
				$this->setBackOrderSupplierAmount($object->id);
				break;
			case 'LINEORDER_SUPPLIER_DISPATCH':
				$this->setBackOrderSupplierAmount($object->id);
				break;
			case 'RECEPTION_VALIDATE':
				$this->setBackOrderSupplierAmount($object->origin_id);
				break;
			//case 'LINEORDER_SUPPLIER_CREATE':
			//case 'LINEORDER_SUPPLIER_UPDATE':
			//case 'LINEORDER_SUPPLIER_DELETE':

			// Proposals
			//case 'PROPAL_CREATE':
			//case 'PROPAL_MODIFY':
			//case 'PROPAL_VALIDATE':
			//case 'PROPAL_SENTBYMAIL':
			//case 'PROPAL_CLOSE_SIGNED':
			//case 'PROPAL_CLOSE_REFUSED':
			//case 'PROPAL_DELETE':
			//case 'LINEPROPAL_INSERT':
			//case 'LINEPROPAL_UPDATE':
			//case 'LINEPROPAL_DELETE':

			// SupplierProposal
			//case 'SUPPLIER_PROPOSAL_CREATE':
			//case 'SUPPLIER_PROPOSAL_MODIFY':
			//case 'SUPPLIER_PROPOSAL_VALIDATE':
			//case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
			//case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
			//case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
			//case 'SUPPLIER_PROPOSAL_DELETE':
			//case 'LINESUPPLIER_PROPOSAL_INSERT':
			//case 'LINESUPPLIER_PROPOSAL_UPDATE':
			//case 'LINESUPPLIER_PROPOSAL_DELETE':

			// Contracts
			//case 'CONTRACT_CREATE':
			//case 'CONTRACT_MODIFY':
			//case 'CONTRACT_ACTIVATE':
			//case 'CONTRACT_CANCEL':
			//case 'CONTRACT_CLOSE':
			//case 'CONTRACT_DELETE':
			//case 'LINECONTRACT_INSERT':
			//case 'LINECONTRACT_UPDATE':
			//case 'LINECONTRACT_DELETE':

			// Bills
			//case 'BILL_CREATE':
			//case 'BILL_MODIFY':
			//case 'BILL_VALIDATE':
			//case 'BILL_UNVALIDATE':
			//case 'BILL_SENTBYMAIL':
			//case 'BILL_CANCEL':
			//case 'BILL_DELETE':
			//case 'BILL_PAYED':
			//case 'LINEBILL_INSERT':
			//case 'LINEBILL_UPDATE':
			//case 'LINEBILL_DELETE':

			//Supplier Bill
			//case 'BILL_SUPPLIER_CREATE':
			//case 'BILL_SUPPLIER_UPDATE':
			//case 'BILL_SUPPLIER_DELETE':
			//case 'BILL_SUPPLIER_PAYED':
			//case 'BILL_SUPPLIER_UNPAYED':
			//case 'BILL_SUPPLIER_VALIDATE':
			//case 'BILL_SUPPLIER_UNVALIDATE':
			//case 'LINEBILL_SUPPLIER_CREATE':
			//case 'LINEBILL_SUPPLIER_UPDATE':
			//case 'LINEBILL_SUPPLIER_DELETE':

			// Payments
			//case 'PAYMENT_CUSTOMER_CREATE':
			//case 'PAYMENT_SUPPLIER_CREATE':
			//case 'PAYMENT_ADD_TO_BANK':
			//case 'PAYMENT_DELETE':

			// Online
			//case 'PAYMENT_PAYBOX_OK':
			//case 'PAYMENT_PAYPAL_OK':
			//case 'PAYMENT_STRIPE_OK':

			// Donation
			//case 'DON_CREATE':
			//case 'DON_UPDATE':
			//case 'DON_DELETE':

			// Interventions
			//case 'FICHINTER_CREATE':
			//case 'FICHINTER_MODIFY':
			//case 'FICHINTER_VALIDATE':
			//case 'FICHINTER_DELETE':
			//case 'LINEFICHINTER_CREATE':
			//case 'LINEFICHINTER_UPDATE':
			//case 'LINEFICHINTER_DELETE':

			// Members
			//case 'MEMBER_CREATE':
			//case 'MEMBER_VALIDATE':
			//case 'MEMBER_SUBSCRIPTION':
			//case 'MEMBER_MODIFY':
			//case 'MEMBER_NEW_PASSWORD':
			//case 'MEMBER_RESILIATE':
			//case 'MEMBER_DELETE':

			// Categories
			//case 'CATEGORY_CREATE':
			//case 'CATEGORY_MODIFY':
			//case 'CATEGORY_DELETE':
			//case 'CATEGORY_SET_MULTILANGS':

			// Projects
			//case 'PROJECT_CREATE':
			//case 'PROJECT_MODIFY':
			//case 'PROJECT_DELETE':

			// Project tasks
			//case 'TASK_CREATE':
			//case 'TASK_MODIFY':
			//case 'TASK_DELETE':

			// Task time spent
			//case 'TASK_TIMESPENT_CREATE':
			//case 'TASK_TIMESPENT_MODIFY':
			//case 'TASK_TIMESPENT_DELETE':

			// Shipping
			//case 'SHIPPING_CREATE':
			case 'SHIPPING_MODIFY':
				// set backorder Amount again
				$this->setBackOrderAmount($object->origin_id);
				break;
			case 'SHIPPING_VALIDATE':
				// set backorder Amount again
				$this->setBackOrderAmount($object->origin_id);
				break;
			//case 'SHIPPING_SENTBYMAIL':
			//case 'SHIPPING_BILLED':
			//case 'SHIPPING_CLOSED':
			//case 'SHIPPING_REOPEN':
			case 'SHIPPING_DELETE':
				// set backorder Amount again
				$this->setBackOrderAmount($object->origin_id, 'validate', $object->id);
				break;
			case 'RECEPTION_MODIFY':
				// set backorder Amount again
				$this->setBackOrderSupplierAmount($object->origin_id);
				break;
			case 'RECEPTION_DELETE':
				// set backorder Amount again (supplier order can have more receptions)
				$this->setBackOrderSupplierAmount($object->origin_id, 'update', $object->id);
				break;
			case 'LINERECEPTION_MODIFY':
			case 'LINERECEPTION_UPDATE':
				// set backorder Amount again for line
				if ((int) DOL_VERSION < 20) {
					/** @var CommandeFournisseurDispatch $object */
					$this->setBackOrderSupplierAmount($object->fk_commande, 'update');
				} else {
					/** @var ReceptionLineBatch $object */
					$this->setBackOrderSupplierAmount($object->fk_element, 'update');
				}
				break;
			case 'LINERECEPTION_DELETE':
				// set backorder Amount again and delete qty (supplier order can have more receptions)
				if ((int) DOL_VERSION < 20) {
					/** @var CommandeFournisseurDispatch $object */
					$object->fetch($object->id);
					$this->setBackOrderSupplierAmount($object->fk_commande, 'update', null, $object->fk_commandefourndet, $object->qty);
				} else {
					/** @var ReceptionLineBatch $object */
					$object->fetch($object->id);
					$this->setBackOrderSupplierAmount($object->fk_element, 'update', null, $object->fk_elementdet, $object->qty);
				}
				break;
			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}

	/**
	 * update backorder amount from customer order
	 *
	 * @param int    $fk_commande 			order object id
	 * @param string $mode 					read, update or delete
	 * @param int    $fk_expedition_delete	Id of deleted shipment
	 * @param int    $fk_commandedet		Id of order line
	 * @param float  $delete_qty			deleted qty from trigger.
	 *
	 * @return void
	 */
	private function setBackOrderAmount($fk_commande, $mode = 'update', $fk_expedition_delete = null, $fk_commandedet = null, $delete_qty = null)
	{
		global $conf;

		if (!empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			dol_include_once('/backorder/class/backorder.class.php');
			$backorder = new BackOrder($this->db);
			$result = $backorder->getBackorder($mode, $fk_commande, $fk_commandedet, $fk_expedition_delete, $delete_qty);
			if ($result < 0) {
				setEventMessages($backorder->error, null, 'errors');
			}
		}
	}

	/**
	 * update backorder amount from supplier order
	 *
	 * @param int    $fk_commande 			order object id
	 * @param string $mode 					read, update, order  or delete
	 * @param int    $fk_reception_delete	Id of deleted reception
	 * @param int    $fk_commandedet		Id of order line
	 * @param float  $delete_qty			deleted qty from trigger.
	 *
	 * @return void
	 */
	private function setBackOrderSupplierAmount($fk_commande, $mode = 'update', $fk_reception_delete = null, $fk_commandedet = null, $delete_qty = null)
	{
		global $conf;

		if (!empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			dol_include_once('/backorder/class/backorder_supplier.class.php');
			$backorder = new BackOrderSupplier($this->db);
			$result = $backorder->getBackorder($mode, $fk_commande, $fk_commandedet, $fk_reception_delete, $delete_qty);
			if ($result < 0) {
				setEventMessages($backorder->error, null, 'errors');
			}
		}
	}

	/**
	 * update backorder status
	 *
	 * @param User	$user		User that modifies
	 * @param int	$productId	product id
	 *
	 * @return void
	 */
	private function setBackOrderStatus($user, $productId)
	{
		global $conf;

		if (!empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			dol_include_once('/backorder/class/backorder.class.php');
			$backorder = new BackOrder($this->db);
			$result = $backorder->setStatus($user, $productId);
			if ($result < 0) {
				setEventMessages($backorder->error, null, 'errors');
			}
		}
	}
}
