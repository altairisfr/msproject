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
 * \file    backorder/class/actions_backorder.class.php
 * \ingroup backorder
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsBackOrder
 */
class ActionsBackOrder
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;
	/**
	 * @var string Error
	 */
	public $error = '';
	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	private $totalBackOrder;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db,$langs,$conf,$user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$error = 0; // Error counter
		$errors = array();

		if (in_array($parameters['currentcontext'], array('ordercard'))) {
			if (!empty($object->id)) {
				if ($action == 'generate_backorder_doc') {
					dol_include_once('/backorder/doc/pdf_darwin.modules.php');
					$pdfBackOrder = new pdf_darwin($this->db);
					if ($pdfBackOrder->write_file($object, $langs, '', 0, 1) > 0) { // no product descriptions
						setEventMessages($langs->trans("Generated"), null, 'mesgs');
					} else {
						$errors = $pdfBackOrder->errors;
						$error--;
					}
				}
			}
		}
		if (! $error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $errors;
			return -1;
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		if (in_array($parameters['currentcontext'], array('ordercard')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				/** @var Commande $object */
				dol_include_once('/backorder/class/backorder.class.php');
				$langs->load('backorder@backorder');
				$backOrder = new BackOrder($this->db);
				$backOrderQty = $backOrder->getBackOrderQty($object->id);
				if ($backOrderQty > 0) {
					$buttonParams['attr']['title'] = $langs->trans('BackorderQty', price($backOrderQty));
					print dolGetButtonAction($langs->trans('BackOrderDoc'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=generate_backorder_doc&token='.newToken(), '', 1, $buttonParams);
				} else {
					$buttonParams['attr']['title'] = $langs->trans('NoBackorders');
					print dolGetButtonAction($langs->trans('BackOrderDoc'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=generate_backorder_doc&token='.newToken(), '', 0, $buttonParams);
				}
				
			}
		}
		return $error;
	}

	/**
	 * Overloading the printFieldListSelect function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListSelect($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$this->resprints = ', (SELECT MAX(r.order_amount) FROM '.MAIN_DB_PREFIX.'backorder_backorder r WHERE r.fk_commande = c.rowid) as backorder_amount';
			}
		}
		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$this->resprints = ', (SELECT MAX(r.order_amount) FROM '.MAIN_DB_PREFIX.'backorder_backorder_supplier r WHERE r.fk_commande = cf.rowid) as backorder_amount';
			}
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListSelect function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListWhere($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$search_backorder = GETPOST('search_backorder', 'alpha');
				if ($search_backorder != '') {
					$this->resprints = natural_search('(SELECT MAX(r.order_amount) FROM '.MAIN_DB_PREFIX.'backorder_backorder r WHERE r.fk_commande = c.rowid)', $search_backorder, 1);
				}
			}
		}
		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$search_backorder = GETPOST('search_backorder', 'alpha');
				if ($search_backorder != '') {
					$this->resprints = natural_search('(SELECT MAX(r.order_amount) FROM '.MAIN_DB_PREFIX.'backorder_backorder_supplier r WHERE r.fk_commande = cf.rowid)', $search_backorder, 1);
				}
			}
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListOption($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$search_backorder = GETPOST('search_backorder', 'alpha');
				$this->resprints = '<td class="liste_titre right">';
				$this->resprints .= '<input class="flat" type="text" size="4" name="search_backorder" value="'.$search_backorder.'">';
				$this->resprints .= '</td>';
			}
		}
		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$search_backorder = GETPOST('search_backorder', 'alpha');
				$this->resprints = '<td class="liste_titre right">';
				$this->resprints .= '<input class="flat" type="text" size="4" name="search_backorder" value="'.$search_backorder.'">';
				$this->resprints .= '</td>';
			}
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$sortfield = $parameters['sortfield'];
				$sortorder = $parameters['sortorder'];
				$param = $parameters['param'];
				$this->resprints = getTitleFieldOfList($langs->trans('BackOrder'), 0, $_SERVER["PHP_SELF"], 'backorder_amount', '', $param, '', $sortfield, $sortorder, 'right ');
			}
		}
		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$sortfield = $parameters['sortfield'];
				$sortorder = $parameters['sortorder'];
				$param = $parameters['param'];
				$this->resprints = getTitleFieldOfList($langs->trans('BackOrder'), 0, $_SERVER["PHP_SELF"], 'backorder_amount', '', $param, '', $sortfield, $sortorder, 'right ');
			}
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListValue function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$this->resprints .= '<td></td>';
				$obj = $parameters['obj'];
				$this->resprints = '<td class="right">'.price($obj->backorder_amount).'</td>';
				$this->totalBackOrder += $obj->backorder_amount;
			}
		}

		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$this->resprints .= '<td></td>';
				$obj = $parameters['obj'];
				$this->resprints = '<td class="right">'.price($obj->backorder_amount).'</td>';
				$this->totalBackOrder += $obj->backorder_amount;
			}
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListFooter function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				$this->resprints =  '<tr class="liste_total">';
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					$this->resprints .= '<td></td>';
				}
				$this->resprints .= '<td align="left">'.$langs->trans("TotalBackOrder").'</td>';
				$this->resprints .= '<td class="center">'.price($this->totalBackOrder).'</td>';
				$this->resprints .= '</tr>';
			}
		}
		if (in_array($parameters['currentcontext'], array('supplierorderlist')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				$this->resprints =  '<tr class="liste_total">';
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					$this->resprints .= '<td></td>';
				}
				$this->resprints .= '<td align="left">'.$langs->trans("TotalBackOrder").'</td>';
				$this->resprints .= '<td class="center">'.price($this->totalBackOrder).'</td>';
				$this->resprints .= '</tr>';
			}
		}
		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListValue function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function dashboardOrders($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('ordersindex')) && !empty($conf->global->BACKORDER_CUSTOMER_ORDER)) {
			if ($user->rights->backorder->read) {
				/*
				* Orders that are in backorder
				*/
				if (!empty($conf->commande->enabled)) {
					require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
					require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

					$commandestatic = new Commande($this->db);
					$companystatic = new Societe($this->db);
					$formfile = new FormFile($this->db);

					$max = 10;

					$sql = "SELECT DISTINCT c.rowid, c.entity, c.ref, c.fk_statut, c.facture, c.date_commande as date, s.nom as name, s.rowid as socid";
					$sql .= ", s.client";
					$sql .= ", s.code_client";
					$sql .= ", s.canvas";
					$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
					$sql .= ", ".MAIN_DB_PREFIX."societe as s";
					$sql .= ", ".MAIN_DB_PREFIX."backorder_backorder as bb";
					if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= " WHERE c.fk_soc = s.rowid";
					$sql .= " AND c.rowid = bb.fk_commande";
					$sql .= " AND bb.order_amount <> 0";
					$sql .= " AND c.entity IN (".getEntity('commande').")";
					if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
					$sql .= " ORDER BY c.rowid DESC";

					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						print '<div class="fichetwothirdleft"><div class="fichethirdleft">';
						print '<div class="div-table-responsive-no-min">';
						print '<table class="noborder centpercent">';
						print '<tr class="liste_titre">';
						print '<th colspan="4">'.$langs->trans("OnBackOrderOrders").'</th></tr>';

						if ($num) {
							$i = 0;
							while ($i < $num && $i < $max) {
								$obj = $this->db->fetch_object($resql);
								print '<tr class="oddeven">';
								print '<td width="20%" class="nowrap">';

								$commandestatic->id = $obj->rowid;
								$commandestatic->ref = $obj->ref;

								$companystatic->id = $obj->socid;
								$companystatic->name = $obj->name;
								$companystatic->client = $obj->client;
								$companystatic->code_client = $obj->code_client;
								$companystatic->canvas = $obj->canvas;

								print '<table class="nobordernopadding"><tr class="nocellnopadd">';
								print '<td width="96" class="nobordernopadding nowrap">';
								print $commandestatic->getNomUrl(1);
								print '</td>';

								print '<td width="16" class="nobordernopadding nowrap">';
								print '&nbsp;';
								print '</td>';

								print '<td width="16" class="nobordernopadding hideonsmartphone right">';
								$filename = dol_sanitizeFileName($obj->ref);
								$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
								print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
								print '</td></tr></table>';

								print '</td>';

								print '<td>';
								print $companystatic->getNomUrl(1, 'company');
								print '</td>';

								print '<td class="right">'.dol_print_date($this->db->jdate($obj->date), 'day').'</td>'."\n";

								print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, $obj->facture, 3).'</td>';

								print '</tr>';
								$i++;
							}
							if ($i < $num) {
								print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
							}
						}
						print "</table></div><br>";
						print '</div></div>';
					} else dol_print_error($this->db);
				}
			}
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}
		/**
	 * Overloading the printFieldListValue function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function dashboardOrdersSuppliers($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderssuppliersindex')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				/*
				* Orders that are in backorder
				*/
				if (!empty($conf->fournisseur->enabled)) {
					require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

					$commandestatic = new CommandeFournisseur($this->db);
					$companystatic = new Societe($this->db);
					$formfile = new FormFile($this->db);

					$max = 10;

					$sql = "SELECT DISTINCT c.rowid, c.entity, c.ref, c.fk_statut, c.date_commande as date, s.nom as name, s.rowid as socid";
					$sql .= ", s.client";
					$sql .= ", s.code_client";
					$sql .= ", s.canvas";
					$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
					$sql .= ", ".MAIN_DB_PREFIX."societe as s";
					$sql .= ", ".MAIN_DB_PREFIX."backorder_backorder_supplier as bb";
					if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
					$sql .= " WHERE c.fk_soc = s.rowid";
					$sql .= " AND c.rowid = bb.fk_commande";
					$sql .= " AND bb.order_amount <> 0";
					$sql .= " AND c.entity IN (".getEntity('commande').")";
					if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
					$sql .= " ORDER BY c.rowid DESC";

					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						print '<div class="fichetwothirdright"><div class="ficheaddleft">';
						print '<div class="div-table-responsive-no-min">';
						print '<table class="noborder centpercent">';
						print '<tr class="liste_titre">';
						print '<th colspan="4">'.$langs->trans("OnBackOrderOrders").'</th></tr>';

						if ($num) {
							$i = 0;
							while ($i < $num && $i < $max) {
								$obj = $this->db->fetch_object($resql);
								print '<tr class="oddeven">';
								print '<td width="20%" class="nowrap">';

								$commandestatic->id = $obj->rowid;
								$commandestatic->ref = $obj->ref;

								$companystatic->id = $obj->socid;
								$companystatic->name = $obj->name;
								$companystatic->client = $obj->client;
								$companystatic->code_client = $obj->code_client;
								$companystatic->canvas = $obj->canvas;

								print '<table class="nobordernopadding"><tr class="nocellnopadd">';
								print '<td width="96" class="nobordernopadding nowrap">';
								print $commandestatic->getNomUrl(1);
								print '</td>';

								print '<td width="16" class="nobordernopadding nowrap">';
								print '&nbsp;';
								print '</td>';

								print '<td width="16" class="nobordernopadding hideonsmartphone right">';
								$filename = dol_sanitizeFileName($obj->ref);
								$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
								print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
								print '</td></tr></table>';

								print '</td>';

								print '<td>';
								print $companystatic->getNomUrl(1, 'company');
								print '</td>';

								print '<td class="right">'.dol_print_date($this->db->jdate($obj->date), 'day').'</td>'."\n";

								print '<td class="right">'.$commandestatic->LibStatut($obj->fk_statut, 3).'</td>';

								print '</tr>';
								$i++;
							}
							if ($i < $num) {
								print '<tr><td><span class="opacitymedium">'.$langs->trans("More").'...</span></td><td></td><td></td><td></td></tr>';
							}
						}
						print "</table></div><br>";
						print '</div></div>';
					} else dol_print_error($this->db);
				}
			}
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 * Overloading the objectLineView_BeforeProduct function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function objectLineView_BeforeProduct($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('ordercard')) && getDolGlobalInt('BACKORDER_CUSTOMER_ORDER')) {
			if ($user->rights->backorder->read) {
				/** @var Commande $object */
				dol_include_once('/backorder/class/backorder.class.php');
				$langs->load('backorder@backorder');
				$line = $parameters['line'];
				$backOrder = new BackOrder($this->db);
				$backOrderId = $backOrder->getBackOrderId($object->id, $line->id);
				if ($backOrderId > 0) {
					$backOrder->fetch($backOrderId);
					$this->resprints = $backOrder->getLibStatut(3) . ' ';
				}
			}
		}
		if (in_array($parameters['currentcontext'], array('ordersuppliercard')) && !empty($conf->global->BACKORDER_SUPPLIER_ORDER)) {
			if ($user->rights->backorder->supplier->read) {
				/** @var CommandeFournisseur $object */
				dol_include_once('/backorder/class/backorder_supplier.class.php');
				$langs->load('backorder@backorder');
				$line = $parameters['line'];
				$backOrder = new BackOrderSupplier($this->db);
				$backOrderQty = $backOrder->getBackOrderQty($object->id, $line->id);
				if ($backOrderQty > 0) {
					$this->resprints = img_picto('', 'warning', 'title="'.$langs->trans('BackorderQty', price($backOrderQty)).'"') . ' ';
				}
			}
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}
}
