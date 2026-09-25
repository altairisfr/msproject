<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       backorder_list.php
 *		\ingroup    backorder
 *		\brief      List page for backorder
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
if (! empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
dol_include_once('/backorder/class/backorder.class.php');

// Load translation files required by the page
$langs->loadLangs(array("backorder@backorder","product","orders","sendings"));

$action     = GETPOST('action', 'aZ09')?GETPOST('action', 'aZ09'):'view';				// The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');											// The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files', 'int');												// Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha');												// Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha');												// We click on a Cancel button
$toselect   = GETPOST('toselect', 'array');												// Array of ids of elements selected into a list
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'backorderlist';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ');												// Option for the css output (always '' except when 'print')
$search_categ = GETPOST("search_categ", 'int');
$search_supplier = GETPOST("search_supplier", 'int');

$id			= GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; } // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object=new BackOrder($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->backorder->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('backorderlist'));     // Note that conf->hooks_modules contains array

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortorder) $sortorder="ASC";

// Security check
$socid=0;
if ($user->socid > 0) {	// Protection if external user
	accessforbidden();
}
//$result = restrictedArea($user, 'backorder', $id, '');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach ($object->fields as $key => $val) {
	if (isset($val['search']) && GETPOST('search_'.$key, 'alpha')) {
		$search[$val['search']] = GETPOST('search_'.$key, 'alpha');
	} elseif ($key == 'status') {
		$search['status'] = GETPOST('search_status', 'alpha');
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (isset($val['search']) && isset($val['searchall'])) $fieldstosearchall[$val['search']]=$val['label'];
}

// Definition of fields for list
$arrayfields=array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (! empty($val['visible'])) $arrayfields[$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

/*
 * Actions
 *
 * Put here all code to do according to value of "$action" parameter
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) {	// All tests are required to be compatible with all browsers
		foreach ($object->fields as $key => $val) {
			if ($val['search']) $search[$val['search']]='';
		}
		$toselect='';
		$search_array_options=array();
		$search_categ=0;
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass='BackOrder';
	$objectlabel='BackOrder';
	$permtoread = $user->rights->backorder->read;
	$uploaddir = $conf->backorder->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 *
 * Put here all code to render page
 */

$form=new Form($db);
$htmlother=new FormOther($db);

$now=dol_now();

//$help_url="EN:Module_BackOrder|FR:Module_BackOrder_FR|ES:Módulo_BackOrder";
$help_url='';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("BackOrders"));


// Build and execute select
// --------------------------------------------------------------------

// List of mass actions available
$arrayofmassactions =  array(
	//'presend'=>$langs->trans("SendByMail"),
	//'builddoc'=>$langs->trans("PDFMerge"),
);
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);
$arrayofselected=is_array($toselect)?$toselect:array();
$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
$selectedfields.=(count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

// only select fields that are needed for query for distinct values
$keysToSelect = array('fk_commande');// fields needed for query
foreach ($object->fields as $key => $val) {
	if (!in_array($key, $keysToSelect) && ! empty($arrayfields[$key]['checked'])) {
		$keysToSelect[] = $key;
	}
}
if (!in_array('fk_product', $keysToSelect)) {
	foreach ($keysToSelect as $key) {
		if (in_array($key, array('qty', 'eqty', 'line_qty', 'line_amount', 'product_qty', 'product_stock', 'price'))) {
			$arrayfields['fk_product']['checked'] = '1';
			$keysToSelect[] = 'fk_product';
			break;
		}
	}
}
if ($sortfield =='fk_product' && !in_array('fk_product', $keysToSelect)) $sortfield = '';
if (! $sortfield && in_array('fk_product', $keysToSelect)) $sortfield='fk_product';
if (! $sortfield) $sortfield='t.fk_commande';
$sqlGroupByFields = '';
$sql = 'SELECT DISTINCT ';
foreach ($object->fields as $key => $val) {
	if (in_array($key, $keysToSelect)) {
		if ($key == 'status' && in_array('fk_product', $keysToSelect)) {
			$sql.='t.'.$key.', ';
			$sqlGroupByFields.= 't.'.$key.', ';
		} elseif ($key == 'status') {
			$sql.='MAX(t.'.$key.') as status, ';
		} else {
			$sql.='t.'.$key.', ';
			$sqlGroupByFields.= 't.'.$key.', ';
		}
	}
}
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/, $/', '', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (! empty($search_categ) || ! empty($search_supplier) || ! empty($search)) {
	if (in_array('fk_product', $keysToSelect)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."commande as c ON c.rowid = t.fk_commande"; // We'll need this table joined to the select in order to filter by categ or supplier
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc";
}
if (! empty($search_categ) && in_array('fk_product', $keysToSelect)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
if (! empty($search_supplier) && in_array('fk_product', $keysToSelect)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pfp.fk_product = p.rowid"; // We'll need this table joined to the select in order to filter by supplier
if (!empty($object->fields['fk_projet']['enabled']) && !empty($search[$object->fields['fk_projet']['search']])) {
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet pj ON pj.rowid = t.fk_projet';
}
$parameters=array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=" WHERE ((order_amount <> 0 AND line_amount <> 0) OR (order_qty <> 0 AND line_qty <> 0))";
if ($object->ismultientitymanaged == 1) $sql.= " AND t.entity IN (".getEntity($object->entityElement).")";
foreach ($search as $key => $val) {
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search=(($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key]))?1:0);
	if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
		if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
			$search[$key] = '';
		}
		$mode_search = 2;
	}
	if ($search[$key] != '') {
		if ($key == 'status') {
			if (!$sqlGroupByFields) {
				$sql.=natural_search('t.status', $search[$key], 2);
			}
		} else {
			$sql.=natural_search($key, $search[$key], $mode_search);
		}
	}
}
if ($search_all) $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
if ($search_supplier > 0)   $sql.= " AND pfp.fk_soc = ".$db->escape($search_supplier);
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
if ($sqlGroupByFields && in_array('status', $keysToSelect) && !in_array('fk_product', $keysToSelect)) {
	$sqlGroupByFields=preg_replace('/, $/', '', $sqlGroupByFields);
	$sql.= ' GROUP BY '.$sqlGroupByFields;
}
if (in_array('qty', $keysToSelect) && in_array('eqty', $keysToSelect)) {
	$sql.= " HAVING qty <> eqty OR eqty IS NULL"; // only backorder
} elseif ($sqlGroupByFields && in_array('status', $keysToSelect) && !in_array('fk_product', $keysToSelect)) {
	foreach ($search as $key => $val) {
		if ($key == 'status' && $search[$key] == -1) continue;
		if ($search[$key] != '') {
			if ($key == 'status') {
				$sql.= ' HAVING MAX(t.status) = '. (int) $search[$key];
			}
		}
	}
}
$sql.=$db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords) {
	$num = $nbtotalofrecords;
} else {
	$sql.= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if (! $resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
foreach ($object->fields as $key => $val) {
	if (isset($val['search']) && isset($search[$val['search']])) $param.= '&search_'.$key.'='.urlencode($search[$val['search']]);
	elseif (isset($search['status'])) $param.= '&search_status='.urlencode($search['status']);
}
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
if ($search_categ > 0) $param.='&search_categ='.urlencode($search_categ);
if ($search_supplier > 0) $param.='&search_supplier='.urlencode($search_supplier);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton='';

// Add name of the list  backorder in the top of list
// --------------------------------------------------------------------
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, $newcardbutton, '', $limit);

// Add code for pre mass action (confirmation or email presend form)
// --------------------------------------------------------------------
$topicmail="SendBackOrderRef";
$modelmail="backorder";
$objecttmp=new BackOrder($db);
$moreforfilter = '';
// Filter on categories
// --------------------------------------------------------------------
if (! empty($conf->categorie->enabled)) {
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('Categories'). ': ';
	$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_PRODUCT, $search_categ, 'search_categ', 1);
	$moreforfilter.='</div>';
}

// Filter on supplier
// --------------------------------------------------------------------
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('Suppliers'). ': ';
$moreforfilter.=$form->select_company($search_supplier, 'search_supplier', 'fournisseur=1', 'SelectThirdParty');
$moreforfilter.='</div>';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	$align='';
	$searchField = '';
	$searchValue = '';
	if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
	if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
	if ($key == 'status') {
		$align.=($align?' ':'').'center';
		$searchField = $form->selectarray('search_'.$key, $val['arrayofkeyvalshort'], (isset($search[$key]) ? $search[$key] : ''), 1, 0, 0, '', 1, 0, 0, '', 'maxwidth100'.($key == 'status' ? ' search_status width100 onrightofpage' : ''), 1);
	} elseif (isset($val['search'])) {
		if (isset($search[$val['search']])) $searchValue = dol_escape_htmltag($search[$val['search']]);
		$searchField = '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.$searchValue.'">';
	}
	if (! empty($arrayfields[$key]['checked'])) print '<td class="liste_titre'.($align?' '.$align:'').'">'.$searchField.'</td>';
}

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
	$align='';
	if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
	if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
	if (in_array($val['type'], array('double(24,8)'))) $align.=($align?' ':'').'right';
	if ($key == 'status') $align.=($align?' ':'').'center';
	if (! empty($arrayfields[$key]['checked'])) print getTitleFieldOfList($arrayfields[$key]['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine=0;

// Loop on record
// --------------------------------------------------------------------
$i=0;
$totalarray=array('nbfield' => 0, 'val' => array(), 'pos' => array());
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break;		// Should not happen

	// Store properties in $object
	$object->id = $obj->fk_commande;
	foreach ($object->fields as $key => $val) {
		if (isset($obj->$key)) $object->$key = $obj->$key;
	}

	// Show here line of result
	print '<tr class="oddeven">';
	foreach ($object->fields as $key => $val) {
		$align='';
		if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
		if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
		if (in_array($val['type'], array('double(24,8)'))) $align.=($align?' ':'').'right';
		if ($key == 'status') $align.=($align?' ':'').'center';
		if (! empty($arrayfields[$key]['checked'])) {
			print '<td';
			if ($align) print ' class="'.$align.'"';
			print '>';
			print $object->showOutputField($val, $key, $obj, '');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! empty($val['isameasure']) && $key != 'order_amount' && $key != 'order_qty') {
				if (! $i) $totalarray['pos'][$totalarray['nbfield']]=$key;
				isset($totalarray['val'][$key]) ? $totalarray['val'][$key] += $obj->$key : $totalarray['val'][$key] = $obj->$key;
			}
		}
	}
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap" align="center">';
	if ($massactionbutton || $massaction) {	// If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		$selected=0;
		if (in_array($obj->rowid, $arrayofselected)) $selected=1;
		print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
	}
	print '</td>';
	if (! $i) $totalarray['nbfield']++;

	print '</tr>';

	$i++;
}

// Show total line
if (isset($totalarray['pos'])) {
	print '<tr class="liste_total">';
	$i=0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (! empty($totalarray['pos'][$i])) {
			print '<td align="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
		} else {
			if ($i == 1) {
				if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
				else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
			} else print '<td></td>';
		}
	}
	print '</tr>';
}

// If no record found
if ($num == 0) {
	$colspan=1;
	foreach ($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', $arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;', '&', $param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->backorder->read;
	$delallowed=$user->rights->backorder->create;

	print $formfile->showdocuments('massfilesarea_backorder', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
