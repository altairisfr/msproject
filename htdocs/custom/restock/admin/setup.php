<?php
/* Copyright (C) 2004-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2013-2022	Charlene BENKE		<charlie@patas-monkey.com> 
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    restock/admin/restock.php
 * \ingroup restock
 * \brief   restock setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../core/lib/restock.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "restock@restock"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
);



/*
 * Actions
 */

if ((float) DOL_VERSION >= 6)
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}


if ($action == 'updateMask') {
	$maskconst=GETPOST('maskconst', 'alpha');
	$maskvalue=GETPOST('maskvalue', 'alpha');
	if ($maskconst)
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
		$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
	else
		$mesg = "<font class='error'>".$langs->trans("Error")."</font>";
}

// TODO Verifier si module numerotation choisi peut etre active
// par appel methode canBeActivated
if ($action == 'setmod')
	dolibarr_set_const($db, "RESTOCK_ADDON", $value, 'chaine', 0, '', $conf->entity);

if ($action == 'set') {
	$label = GETPOST('label', 'alpha');
	$expldir = GETPOST('expldir', 'alpha');

	$type='restock';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model";
	$sql.= " (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$db->escape($value)."','".$type."',".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($expldir)?"'".$db->escape($expldir)."'":"null");
	$sql.= ")";
	if ($db->query($sql)) {
	}
}

if ($action == 'del') {
	$type='restock';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$db->escape($value)."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	if ($db->query($sql))
		if ($conf->global->RESTOCK_ADDON_PDF == "$value") 
			dolibarr_del_const($db, 'RESTOCK_ADDON_PDF', $conf->entity);

}

if ($action == 'setdoc') {
	$label = GETPOST('label', 'alpha');
	$expldir = GETPOST('expldir', 'alpha');

	$db->begin();

	if (dolibarr_set_const($db, "RESTOCK_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
		$conf->global->RESTOCK_ADDON_PDF = $value;

	// On active le modele
	$type='restock';

	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape($value)."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	dol_syslog("restock/admin/setup.php delete ".$sql_del);
	$result1=$db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model";
	$sql.= " (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$value."', '".$type."', ".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($expldir)?"'".$expldir."'":"null");
	$sql.= ")";
	dol_syslog("restock/admin/setup.php insert ".$sql);
	$result2=$db->query($sql);
	if ($result1 && $result2)
		$db->commit();
	else {
		dol_syslog("restock/admin/setup.php ".$db->lasterror(), LOG_ERR);
		$db->rollback();
	}
}


/*
 * View
 */

$page_name = "RestockSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_restock@restock');

// Configuration header
$head = restock_admin_prepare_head();

dol_fiche_head($head, 'setup',  $langs->trans("restock"), -1, "restock@restock");


/*
 *  percent of order
 */
print_titre($langs->trans("RestockNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width=10% >'.$langs->trans("Name").'</td>';
print '<td width=70% >'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();
$dirmodels= $conf->modules_parts['models'];

//var_dump($dirmodels);
foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/restock/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var=true;

			while (($file = readdir($handle))!==false) {
				if (! is_dir($dir.$file) || 
					(substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')) {
					$filebis = $file;
					$classname = preg_replace('/\.php$/', '', $file);
					// For compatibility
					if (! is_file($dir.$filebis)) {
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_restock_".$file;
					}
					if (! class_exists($classname) 
							&& is_readable($dir.$filebis) 
							&& (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) 
							&& substr($filebis, dol_strlen($filebis)-3, 3) == 'php') {
						// Chargement de la classe de numerotation
						require_once($dir.$filebis);
						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled()) {
							$var = !$var;
							print '<tr '.$bc[$var].'><td width="100">';
							echo preg_replace('/mod_restock_/', '', preg_replace('/\.php$/', '', $file));
							print "</td><td>\n";
							print $module->info();

							print '</td>';

							// Show example of numbering module
							print '<td nowrap="nowrap">';
							$tmp=$module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								$langs->load("errors"); 
								print '<div class="error">'.$langs->trans($tmp).'</div>'; 
							}
							elseif ($tmp=='NotConfigured') 
								print $langs->trans($tmp);
							else print $tmp;
							print '</td>'."\n";

							print '<td align="center">';
							//print "> ".$conf->global->RESTOCK_ADDON." - ".$file;
							if ( !empty($conf->global->RESTOCK_ADDON) 
								&& ($conf->global->RESTOCK_ADDON == $file || $conf->global->RESTOCK_ADDON.'.php' == $file))
								print img_picto($langs->trans("Activated"), 'switch_on');
							else {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod';
								print '&value='.preg_replace('/\.php$/', '', $file);
								print '&expldir='.$module->scandir;
								print '&token='.newToken();
								print '&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">';
								print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							// Example for standard invoice
							$htmltooltip='';
							$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval=$module->getNextValue($mysoc, '');
							if ("$nextval" != $langs->trans("NotAvailable")) {
								$htmltooltip.= $langs->trans("NextValueForRestock").': ';
								if ($nextval)
									$htmltooltip.= $nextval.'<br>';
								else
									$htmltooltip.= $langs->trans($module->error).'<br>';
							}

							print '<td align="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);

							if (!empty($conf->global->RESTOCK_ADDON) && $conf->global->RESTOCK_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
								if (! empty($module->error)) 
									dol_htmloutput_mesg($module->error, '', 'error', 1);

							print '</td>';
							print "</tr>\n";
						}
					}
				}
			}
			closedir($handle);
		}
	}
}
print '</table>';


if (true) {
/*
 *  Document templates generators
 */
print_titre($langs->trans("RestockPDFModules"));

// Load array def with activated templates
$type='restock';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
$sql.= " AND entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows=$db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
} else 
	dol_print_error($db);


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width=10% >'.$langs->trans("Name").'</td>';
print '<td width=70% >'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();
//var_dump($dirmodels );
$var=true;
foreach ($dirmodels as $reldir) {
	foreach (array('','/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/restock".$valdir);
		if (is_dir($dir)) {
			$handle=opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle))!==false)
					$filelist[]=$file;

				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) -16);
							$classname = substr($file, 0, dol_strlen($file) -12);

							require_once($dir.'/'.$file);
							$module = new $classname($db);

							$modulequalified=1;
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

							if ($modulequalified) {
								$var = !$var;
								print '<tr '.$bc[$var].'><td width="100">';
								print (empty($module->name)?$name:$module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info'))
									print $module->info($langs);
								else 
									print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print "<td align='center'>\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print "<td align='center'>\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.$name;
									print '&expldir='.$module->scandir.'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print "<td align='center'>\n";
								if (!empty($conf->global->RESTOCK_ADDON_PDF) && $conf->global->RESTOCK_ADDON_PDF == "$name")
									print img_picto($langs->trans("Default"), 'on');
								else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name;
									print '&expldir='.$module->scandir.'&label='.urlencode($module->name).'"';
									print ' alt="'.$langs->trans("Default").'">';
									print img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip =	''.$langs->trans("Name").': '.$module->name;
								$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height");
									$htmltooltip.=': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								print '<td align="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td align="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&token='.newToken().'&module='.$name.'">';
									print img_object($langs->trans("Preview"), 'bill').'</a>';
								} else
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								print '</td>';
								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}
print '</table>';
} // not yet implemented

print '<br>';



// skip check version of our modules
if ($action == 'patasMonkeySkipCheckVersion') {
	dolibarr_set_const($db, "PATASMONKEY_SKIP_CHECKVERSION", GETPOST('value'), 'chaine', 0, '', $conf->entity);
}
$patasMonkeySkipCheckVersion=$conf->global->PATASMONKEY_SKIP_CHECKVERSION;
print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">';

print '<td width="200px">'.$langs->trans("PatasMonkeySkipCheckVersion").'</td>';
print '<td>'.$langs->trans("InfoPatasMonkeySkipCheckVersion").'</td>';
print '<td colspan=2>';
if ( $patasMonkeySkipCheckVersion == 1) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=patasMonkeySkipCheckVersion&token='.newToken().'&value=0">';
	print img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=patasMonkeySkipCheckVersion&token='.newToken().'&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td></tr>';
print "</table>";
/*
 *  Infos pour le support
 */
//print '<br>';//
libxml_use_internal_errors(true);
$sxe = simplexml_load_string(nl2br(file_get_contents('../changelog.xml')));
if ($sxe === false) {
	echo "Erreur lors du chargement du XML\n";
	foreach (libxml_get_errors() as $error) 
		print $error->message;
	exit;
} else
	$tblversions=$sxe->Version;

$currentversion = $tblversions[count($tblversions)-1];

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width=20%>'.$langs->trans("SupportModuleInformation").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr><td >'.$langs->trans("DolibarrVersion").'</td><td>'.DOL_VERSION.'</td></tr>'."\n";
print '<tr><td >'.$langs->trans("ModuleVersion").'</td>';
print '<td>'.$currentversion->attributes()->Number." (".$currentversion->attributes()->MonthVersion.')</td></tr>'."\n";
print '<tr><td >'.$langs->trans("PHPVersion").'</td><td>'.version_php().'</td></tr>'."\n";
print '<tr><td >'.$langs->trans("DatabaseVersion").'</td>';
print '<td>'.$db::LABEL." ".$db->getVersion().'</td></tr>'."\n";
print '<tr><td >'.$langs->trans("WebServerVersion").'</td>';
print '<td>'.$_SERVER["SERVER_SOFTWARE"].'</td></tr>'."\n";
print '<tr>'."\n";
print '<td colspan="2">'.$langs->trans("SupportModuleInformationDesc").'</td></tr>'."\n";
print "</table>\n";

// Show messages
dol_htmloutput_mesg($mesg, '', 'ok');


// Page end
dol_fiche_end();

llxFooter();
$db->close();	