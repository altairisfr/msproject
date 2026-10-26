<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Nicolas SILOBRE <nsilobre@ns-info90.fr>
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
 * \file    bompdf/admin/setup.php
 * \ingroup bompdf
 * \brief   Bompdf setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
dol_include_once('/bompdf/lib/bompdf.lib.php');
dol_include_once('/nsinfo/lib/setup.lib.php');

// Translations
$langs->loadLangs(array("admin", "bompdf@bompdf"));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$var = GETPOST('var', 'alpha');
$vartxt = GETPOST('vartxt', 'alpha');
$value = GETPOST($var, 'alpha');
$valuetxt = GETPOST($vartxt, 'alpha');
$varcolor = GETPOST('varcolor', 'alpha');
$varround = GETPOST('varround', 'alpha');
$value = GETPOST('value', 'alpha');

$link = DOL_URL_ROOT . '/admin/bom.php';
$link2 = DOL_URL_ROOT . '/admin/mrp.php';

$arrayofparameters = array(
    'BOM_LINK' => array('type' => 'link', 'rootfile' => $link, 'enabled' => 1),
    'OF_LINK' => array('type' => 'link', 'rootfile' => $link2, 'enabled' => 1),
    'TABDEB' => array('type' => 'tabdeb'),
    'BOM_LARG_REF' => array('type' => 'round', 'default' => 20, 'min' => 20, 'max' => 60, 'enabled' => 1),
    'BOM_NBDECIMALE' => array('type' => 'round', 'default' => 5, 'min' => 0, 'max' => 8, 'enabled' => 1),
    'TABFIN' => array('type' => 'tabfin'),

    'TITRE1' => array('type' => 'titletab', 'title' => 'BOMOption'),
    'TABDEB2' => array('type' => 'tabdeb'),
    'BOM_COUTTOTAL' => array('type' => 'bool'),
    'TABFIN2' => array('type' => 'tabfin'),
);

$error = 0;
$setupnotempty = 0;
$defaultvalue = array(0);
$defaultvalue = $arrayofparameters[$var]['default'];

/*
 * Actions
 */

if ((float)DOL_VERSION >= 6) include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

nsinfoAction($action, $var, $value, $vartxt, $varround, $valuetxt, $defaultvalue, $varcolor);

/*
 * View
 */
$formother = new FormOther($db);
$form = new Form($db);

$help_url = '';
$page_name = "BompdfSetup";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_bompdf@bompdf');

// Configuration header
$head = bompdfAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "bompdf@bompdf");

nsinfoSetup($arrayofparameters);

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
