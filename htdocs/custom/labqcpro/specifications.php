<?php
/*
 * LabQC Pro for Dolibarr
 * Copyright (C) 2026 PanciucSoft
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
/*
 * LabQC Pro bootstrap helper.
 */

// Load Dolibarr environment (compatible with standard and custom module directories).
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] === $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1)."/main.inc.php")) {
    $res = @include substr($tmp, 0, $i + 1)."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, $i + 1))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, $i + 1))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die('Error: Cannot load Dolibarr main.inc.php');
}

if (empty($conf->labqcpro->enabled)) {
    accessforbidden();
}

require_once __DIR__.'/lib/labqcpro.lib.php';
$langs->loadLangs(array('labqcpro@labqcpro'));

if (!labqcproUserCan($user, 'qc', 'read')) {
    accessforbidden($langs->trans('LabQCNoReadPermission'));
}

$canWriteSpecifications = labqcproUserCan($user, 'specification', 'write');

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');

// Prevent state-changing operations through GET links; keep Dolibarr CSRF validation enabled.
if (in_array($action, array('addspec', 'updatespec', 'addline', 'deleteline'), true) && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    accessforbidden('POST required for QC specification mutations');
}

// Verify that the QC tables exist before using them.
$tableCheck = $db->query("SELECT 1 FROM ".MAIN_DB_PREFIX."labqcpro_specification LIMIT 1");
if (!$tableCheck) {
    llxHeader('', $langs->trans('LabQCSpecifications'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'?v=1.1.0" />';
    print load_fiche_titre($langs->trans('LabQCSpecifications'), $langs->trans('LabQCProductAnalyticalSpecifications'), 'product');
    print '<div class="warning">';
    print $langs->trans('LabQCTablesNotInstalled').' ';
    print $langs->trans('LabQCReactivateModule');
    print '</div>';
    llxFooter();
    $db->close();
    exit;
}

function labqcproStatusLabel($status)
{
    global $langs;
    if ((int) $status === 1) {
        return '<span class="badge badge-status4">'.$langs->trans('LabQCActive').'</span>';
    }
    if ((int) $status === 2) {
        return '<span class="badge badge-status8">'.$langs->trans('LabQCArchived').'</span>';
    }
    return '<span class="badge badge-status1">'.$langs->trans('LabQCDraft').'</span>';
}

function labqcproCriteriaLabel($type, $min, $max, $target, $unit)
{
    $unitText = $unit !== '' ? ' '.dol_escape_htmltag($unit) : '';

    if ($type === 'range') {
        return dol_escape_htmltag($min).' - '.dol_escape_htmltag($max).$unitText;
    }
    if ($type === 'max') {
        return '&le; '.dol_escape_htmltag($max).$unitText;
    }
    if ($type === 'min') {
        return '&ge; '.dol_escape_htmltag($min).$unitText;
    }
    if ($type === 'equal') {
        return '= '.dol_escape_htmltag($target).$unitText;
    }

    return dol_escape_htmltag($target);
}

/** Verify that a selected product belongs to the active entity or is shared. */
function labqcproProductAvailableInEntity($db, $productId, $entity)
{
    if ((int) $productId <= 0) return false;
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product";
    $sql .= " WHERE rowid=".(int) $productId;
    $sql .= " AND entity IN (0, ".(int) $entity.")";
    $resql = $db->query($sql);
    return $resql && $db->num_rows($resql) > 0;
}

/*
 * Actions
 */

if ($action === 'addspec') {
    if (!$canWriteSpecifications) {
        accessforbidden($langs->trans('LabQCNoSpecCreatePermission'));
    }
    $fkProduct = GETPOSTINT('fk_product');
    $ref = trim(GETPOST('ref', 'alphanohtml'));
    $label = trim(GETPOST('label', 'alphanohtml'));
    $version = trim(GETPOST('version', 'alphanohtml'));
    $description = trim(GETPOST('description', 'restricthtml'));
    $status = GETPOSTINT('status');

    if ($fkProduct <= 0 || !labqcproProductAvailableInEntity($db, $fkProduct, (int) $conf->entity) || $ref === '' || $label === '') {
        setEventMessages($langs->trans('LabQCRequiredSpecFields'), null, 'errors');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_specification";
        $sql .= " (entity, fk_product, ref, label, version, status, description, datec, fk_user_creat)";
        $sql .= " VALUES (";
        $sql .= ((int) $conf->entity).",";
        $sql .= ((int) $fkProduct).",";
        $sql .= "'".$db->escape($ref)."',";
        $sql .= "'".$db->escape($label)."',";
        $sql .= "'".$db->escape($version !== '' ? $version : '1.0')."',";
        $sql .= ((int) $status).",";
        $sql .= "'".$db->escape($description)."',";
        $sql .= "'".$db->idate(dol_now())."',";
        $sql .= ((int) $user->id);
        $sql .= ")";

        if ($db->query($sql)) {
            $newId = $db->last_insert_id(MAIN_DB_PREFIX."labqcpro_specification");
            setEventMessages($langs->trans('LabQCSpecificationCreated'), null, 'mesgs');
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $newId);
            exit;
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }
}

if ($action === 'updatespec' && $id > 0) {
    if (!$canWriteSpecifications) {
        accessforbidden($langs->trans('LabQCNoSpecModifyPermission'));
    }
    $fkProduct = GETPOSTINT('fk_product');
    $ref = trim(GETPOST('ref', 'alphanohtml'));
    $label = trim(GETPOST('label', 'alphanohtml'));
    $version = trim(GETPOST('version', 'alphanohtml'));
    $description = trim(GETPOST('description', 'restricthtml'));
    $status = GETPOSTINT('status');

    if ($fkProduct <= 0 || !labqcproProductAvailableInEntity($db, $fkProduct, (int) $conf->entity) || $ref === '' || $label === '') {
        setEventMessages($langs->trans('LabQCRequiredSpecFields'), null, 'errors');
    } else {
        $sql = "UPDATE ".MAIN_DB_PREFIX."labqcpro_specification SET";
        $sql .= " fk_product=".(int) $fkProduct;
        $sql .= ", ref='".$db->escape($ref)."'";
        $sql .= ", label='".$db->escape($label)."'";
        $sql .= ", version='".$db->escape($version !== '' ? $version : '1.0')."'";
        $sql .= ", status=".(int) $status;
        $sql .= ", description='".$db->escape($description)."'";
        $sql .= ", fk_user_modif=".(int) $user->id;
        $sql .= " WHERE rowid=".(int) $id;
        $sql .= " AND entity=".(int) $conf->entity;

        if ($db->query($sql)) {
            setEventMessages($langs->trans('LabQCSpecificationUpdated'), null, 'mesgs');
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }
}

/**
 * Enforce entity ownership before changing child analytical parameters.
 * Child rows do not carry an entity column: check their parent specification.
 */
function labqcproSpecificationBelongsToEntity($db, $specificationId, $entity)
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."labqcpro_specification";
    $sql .= " WHERE rowid=".(int) $specificationId." AND entity=".(int) $entity;
    $resql = $db->query($sql);
    return $resql && $db->num_rows($resql) > 0;
}

if ($action === 'addline' && $id > 0) {
    if (!$canWriteSpecifications) {
        accessforbidden($langs->trans('LabQCNoParameterModifyPermission'));
    }
    if (!labqcproSpecificationBelongsToEntity($db, $id, (int) $conf->entity)) {
        accessforbidden($langs->trans('LabQCNoReadPermission'));
    }
    $parameter = trim(GETPOST('parameter', 'alphanohtml'));
    $method = trim(GETPOST('method', 'alphanohtml'));
    $criteriaType = GETPOST('criteria_type', 'aZ09');
    $minRaw = trim(GETPOST('min_value', 'alphanohtml'));
    $maxRaw = trim(GETPOST('max_value', 'alphanohtml'));
    $targetText = trim(GETPOST('target_text', 'alphanohtml'));
    $unit = trim(GETPOST('unit', 'alphanohtml'));
    $mandatory = GETPOSTINT('mandatory') ? 1 : 0;

    $allowedTypes = array('range', 'max', 'min', 'text', 'equal');
    if (!in_array($criteriaType, $allowedTypes, true)) {
        $criteriaType = 'range';
    }

    $valid = ($parameter !== '');

    if ($criteriaType === 'range' && ($minRaw === '' || $maxRaw === '')) {
        $valid = false;
    }
    if ($criteriaType === 'max' && $maxRaw === '') {
        $valid = false;
    }
    if ($criteriaType === 'min' && $minRaw === '') {
        $valid = false;
    }
    if (($criteriaType === 'text' || $criteriaType === 'equal') && $targetText === '') {
        $valid = false;
    }

    // Reject malformed numeric limits instead of silently converting them to zero.
    // A reversed range cannot be used for meaningful laboratory acceptance criteria.
    if (in_array($criteriaType, array('range', 'min', 'max'), true)) {
        if (($criteriaType === 'range' || $criteriaType === 'min') && $minRaw !== '' && !is_numeric(str_replace(',', '.', $minRaw))) {
            $valid = false;
        }
        if (($criteriaType === 'range' || $criteriaType === 'max') && $maxRaw !== '' && !is_numeric(str_replace(',', '.', $maxRaw))) {
            $valid = false;
        }
        if ($criteriaType === 'range' && $minRaw !== '' && $maxRaw !== ''
            && is_numeric(str_replace(',', '.', $minRaw)) && is_numeric(str_replace(',', '.', $maxRaw))
            && (float) str_replace(',', '.', $minRaw) > (float) str_replace(',', '.', $maxRaw)) {
            $valid = false;
        }
    }

    if (!$valid) {
        setEventMessages($langs->trans('LabQCParameterCriterionRequired'), null, 'errors');
    } else {
        $minSql = 'NULL';
        $maxSql = 'NULL';

        if ($minRaw !== '') {
            $minSql = (string) ((float) str_replace(',', '.', $minRaw));
        }
        if ($maxRaw !== '') {
            $maxSql = (string) ((float) str_replace(',', '.', $maxRaw));
        }

        $sql = "SELECT COALESCE(MAX(position), 0) AS maxpos";
        $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_specification_line";
        $sql .= " WHERE fk_specification=".(int) $id;

        $resql = $db->query($sql);
        $position = 1;
        if ($resql) {
            $obj = $db->fetch_object($resql);
            $position = ((int) $obj->maxpos) + 1;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_specification_line";
        $sql .= " (fk_specification, position, parameter, method, criteria_type, min_value, max_value, target_text, unit, mandatory, datec, fk_user_creat)";
        $sql .= " VALUES (";
        $sql .= ((int) $id).",";
        $sql .= ((int) $position).",";
        $sql .= "'".$db->escape($parameter)."',";
        $sql .= "'".$db->escape($method)."',";
        $sql .= "'".$db->escape($criteriaType)."',";
        $sql .= $minSql.",";
        $sql .= $maxSql.",";
        $sql .= "'".$db->escape($targetText)."',";
        $sql .= "'".$db->escape($unit)."',";
        $sql .= ((int) $mandatory).",";
        $sql .= "'".$db->idate(dol_now())."',";
        $sql .= ((int) $user->id);
        $sql .= ")";

        if ($db->query($sql)) {
            setEventMessages($langs->trans('LabQCParameterAdded'), null, 'mesgs');
            header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $id);
            exit;
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }
}

if ($action === 'deleteline' && $id > 0) {
    if (!$canWriteSpecifications) {
        accessforbidden($langs->trans('LabQCNoParameterDeletePermission'));
    }
    if (!labqcproSpecificationBelongsToEntity($db, $id, (int) $conf->entity)) {
        accessforbidden($langs->trans('LabQCNoReadPermission'));
    }
    $lineId = GETPOSTINT('lineid');

    if ($lineId > 0) {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."labqcpro_specification_line";
        $sql .= " WHERE rowid=".(int) $lineId;
        $sql .= " AND fk_specification=".(int) $id;

        if ($db->query($sql)) {
            setEventMessages($langs->trans('LabQCParameterDeleted'), null, 'mesgs');
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }

    header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $id);
    exit;
}

/*
 * Load selected specification
 */

$spec = null;

if ($id > 0) {
    $sql = "SELECT s.rowid, s.fk_product, s.ref, s.label, s.version, s.status, s.description,";
    $sql .= " p.ref AS product_ref, p.label AS product_label";
    $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_specification AS s";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = s.fk_product";
    $sql .= " WHERE s.rowid=".(int) $id;
    $sql .= " AND s.entity=".(int) $conf->entity;

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        $spec = $db->fetch_object($resql);
    }
}

/*
 * Product list
 */

$products = array();

$sql = "SELECT rowid, ref, label";
$sql .= " FROM ".MAIN_DB_PREFIX."product";
$sql .= " WHERE entity IN (0, ".((int) $conf->entity).")";
$sql .= " ORDER BY ref ASC";

$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $products[] = $obj;
    }
}

/*
 * Page
 */

llxHeader('', $langs->trans('LabQCSpecifications'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'?v=1.1.0" />';

print labqcproPageHero($langs->trans('LabQCSpecifications'), $langs->trans('LabQCProductAnalyticalSpecifications'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-refresh.css', 1).'?v=1.1.0" />';
print '<div class="labqc-spec-page">';


if ($spec) {
    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans('LabQCNewListSpecifications').'</a>';
    print '</div>';

    print '<form class="labqc-spec-detail-form" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $spec->rowid.'">';
    if (!$canWriteSpecifications) {
        print '<fieldset disabled style="border:0;padding:0;margin:0">';
    }
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="updatespec">';
    print '<input type="hidden" name="id" value="'.(int) $spec->rowid.'">';

    print '<div class="labqc-modern-form">';
    print '<div class="labqc-modern-form-title">'.$langs->trans('LabQCSpecification').' '.dol_escape_htmltag($spec->ref).'</div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label titlefieldcreate fieldrequired">'.$langs->trans('LabQCProduct').'</div><div class="labqc-modern-control">';
    print '<select class="flat minwidth300" name="fk_product" required>';
    foreach ($products as $product) {
        $selected = ((int) $product->rowid === (int) $spec->fk_product) ? ' selected' : '';
        print '<option value="'.(int) $product->rowid.'"'.$selected.'>';
        print dol_escape_htmltag($product->ref.' - '.$product->label);
        print '</option>';
    }
    print '</select>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label fieldrequired">'.$langs->trans('LabQCReference').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth200" type="text" name="ref" value="'.dol_escape_htmltag($spec->ref).'" required>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label fieldrequired">'.$langs->trans('LabQCLabel').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth300" type="text" name="label" value="'.dol_escape_htmltag($spec->label).'" required>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCVersion').'</div><div class="labqc-modern-control">';
    print '<input class="flat width100" type="text" name="version" value="'.dol_escape_htmltag($spec->version).'">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCStatus').'</div><div class="labqc-modern-control">';
    print '<select class="flat" name="status">';
    print '<option value="0"'.((int) $spec->status === 0 ? ' selected' : '').'>'.$langs->trans('LabQCDraft').'</option>';
    print '<option value="1"'.((int) $spec->status === 1 ? ' selected' : '').'>'.$langs->trans('LabQCActive').'</option>';
    print '<option value="2"'.((int) $spec->status === 2 ? ' selected' : '').'>'.$langs->trans('LabQCArchived').'</option>';
    print '</select>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCDescription').'</div><div class="labqc-modern-control">';
    print '<textarea class="flat centpercent" rows="3" name="description">'.dol_escape_htmltag($spec->description).'</textarea>';
    print '</div></div>';

    print '</div>'; // labqc-modern-form

    if ($canWriteSpecifications) {
        print '<div class="center">';
        print '<input class="button button-save" type="submit" value="'.$langs->trans('LabQCSaveSpecification').'">';
        print '</div>';
    } else {
        print '</fieldset>';
        print '<div class="info center">'.$langs->trans('LabQCReadOnlySpecification').'</div>';
    }
    print '</form>';

    print '<br>';

    print '<h2 class="labqc-spec-section-title">'.dol_escape_htmltag($langs->trans('LabQCAnalyticalParameters')).'</h2>';

    $sql = "SELECT rowid, position, parameter, method, criteria_type, min_value, max_value, target_text, unit, mandatory";
    $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_specification_line";
    $sql .= " WHERE fk_specification=".(int) $spec->rowid;
    $sql .= " ORDER BY position ASC, rowid ASC";

    $resql = $db->query($sql);

    print '<div class="div-table-responsive labqc-parameter-list">';
    print '<table class="liste centpercent labqc-parameter-table">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans('LabQCParameter').'</th>';
    print '<th>'.$langs->trans('LabQCMethod').'</th>';
    print '<th>'.$langs->trans('LabQCAcceptanceCriterion').'</th>';
    print '<th>'.$langs->trans('LabQCMandatory').'</th>';
    print '<th class="right">'.$langs->trans('LabQCAction').'</th>';
    print '</tr>';

    $lineCount = 0;

    if ($resql) {
        while ($line = $db->fetch_object($resql)) {
            $lineCount++;

            print '<tr class="oddeven">';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCParameter')).'">'.dol_escape_htmltag($line->parameter).'</td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCMethod')).'">'.dol_escape_htmltag($line->method).'</td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCAcceptanceCriterion')).'">'.labqcproCriteriaLabel(
                $line->criteria_type,
                $line->min_value,
                $line->max_value,
                $line->target_text,
                $line->unit
            ).'</td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCMandatory')).'">'.((int) $line->mandatory ? $langs->trans('LabQCYes') : $langs->trans('LabQCNo')).'</td>';
            print '<td class="right" data-label="'.dol_escape_htmltag($langs->trans('LabQCAction')).'">';
            if ($canWriteSpecifications) {
                print '<form style="display:inline" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $spec->rowid.'">';
                print '<input type="hidden" name="token" value="'.newToken().'">';
                print '<input type="hidden" name="action" value="deleteline">';
                print '<input type="hidden" name="id" value="'.(int) $spec->rowid.'">';
                print '<input type="hidden" name="lineid" value="'.(int) $line->rowid.'">';
                print '<input class="button button-delete" type="submit" value="'.$langs->trans('LabQCDelete').'" onclick="return confirm(\''.dol_escape_js($langs->trans('LabQCDeleteParameterConfirm')).'\');">';
                print '</form>';
            } else {
                print '<span class="opacitymedium">—</span>';
            }
            print '</td>';
            print '</tr>';
        }
    }

    if ($lineCount === 0) {
        print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans('LabQCNoParameters').'</span></td></tr>';
    }

    print '</table>';
    print '</div>';

    print '<br>';

    if ($canWriteSpecifications) {
    print '<form class="labqc-spec-param-add-form" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $spec->rowid.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="addline">';
    print '<input type="hidden" name="id" value="'.(int) $spec->rowid.'">';

    print '<div class="labqc-modern-form">';
    print '<div class="labqc-modern-form-title">'.$langs->trans('LabQCAddAnalyticalParameter').'</div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label titlefieldcreate fieldrequired">'.$langs->trans('LabQCParameter').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth300" type="text" name="parameter" placeholder="'.$langs->trans('LabQCExampleAssay').'" required>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCMethod').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth300" type="text" name="method" placeholder="'.$langs->trans('LabQCExampleHPLC').'">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCCriterionType').'</div><div class="labqc-modern-control">';
    print '<select class="flat" name="criteria_type">';
    print '<option value="range">'.$langs->trans('LabQCRange').'</option>';
    print '<option value="max">'.$langs->trans('LabQCMaximum').'</option>';
    print '<option value="min">'.$langs->trans('LabQCMinimum').'</option>';
    print '<option value="equal">'.$langs->trans('LabQCExactTarget').'</option>';
    print '<option value="text">'.$langs->trans('LabQCTextCriterion').'</option>';
    print '</select>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCMinimumValue').'</div><div class="labqc-modern-control">';
    print '<input class="flat width100" type="text" name="min_value" placeholder="98.0">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCMaximumValue').'</div><div class="labqc-modern-control">';
    print '<input class="flat width100" type="text" name="max_value" placeholder="102.0">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCTextTarget').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth300" type="text" name="target_text" placeholder="'.$langs->trans('LabQCExampleWhitePowder').'">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCUnit').'</div><div class="labqc-modern-control">';
    print '<input class="flat width100" type="text" name="unit" placeholder="%">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label">'.$langs->trans('LabQCMandatory').'</div><div class="labqc-modern-control">';
    print '<label><input type="checkbox" name="mandatory" value="1" checked> '.$langs->trans('LabQCRequiredForRelease').'</label>';
    print '</div></div>';

    print '</div>'; // labqc-modern-form

    print '<div class="center">';
    print '<input class="button button-save" type="submit" value="'.$langs->trans('LabQCAddParameter').'">';
    print '</div>';

    print '</form>';
    }
} else {
    print '<div class="fichecenter">';

    print '<div class="fichehalfleft labqc-spec-create">';
    if ($canWriteSpecifications) {
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="addspec">';

    print '<div class="labqc-modern-form labqc-modern-create">';
    print '<div class="labqc-modern-form-title">'.$langs->trans('LabQCCreateSpecification').'</div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel fieldrequired">'.$langs->trans('LabQCProduct').'</div><div class="labqc-modern-control">';
    print '<select class="flat minwidth300" name="fk_product" required>';
    print '<option value="">'.$langs->trans('LabQCSelectProduct').'</option>';
    foreach ($products as $product) {
        print '<option value="'.(int) $product->rowid.'">';
        print dol_escape_htmltag($product->ref.' - '.$product->label);
        print '</option>';
    }
    print '</select>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel fieldrequired">'.$langs->trans('LabQCReference').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth200" type="text" name="ref" placeholder="QC-SPEC-001" required>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel fieldrequired">'.$langs->trans('LabQCLabel').'</div><div class="labqc-modern-control">';
    print '<input class="flat minwidth300" type="text" name="label" placeholder="'.$langs->trans('LabQCFinishedProductSpecification').'" required>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel">'.$langs->trans('LabQCVersion').'</div><div class="labqc-modern-control">';
    print '<input class="flat width100" type="text" name="version" value="1.0">';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel">'.$langs->trans('LabQCStatus').'</div><div class="labqc-modern-control">';
    print '<select class="flat" name="status">';
    print '<option value="0">'.$langs->trans('LabQCDraft').'</option>';
    print '<option value="1">'.$langs->trans('LabQCActive').'</option>';
    print '<option value="2">'.$langs->trans('LabQCArchived').'</option>';
    print '</select>';
    print '</div></div>';

    print '<div class="labqc-modern-field"><div class="labqc-modern-label labqc-spec-fieldlabel">'.$langs->trans('LabQCDescription').'</div><div class="labqc-modern-control">';
    print '<textarea class="flat centpercent" rows="3" name="description"></textarea>';
    print '</div></div>';

    print '</div>'; // labqc-modern-form

    print '<div class="center">';
    print '<input class="button button-save" type="submit" value="'.$langs->trans('LabQCCreateSpecification').'">';
    print '</div>';

    print '</form>';
    } else {
        print '<div class="info">'.$langs->trans('LabQCReadOnlySpecifications').'</div>';
    }
    print '</div>';

    print '<div class="fichehalfright labqc-spec-list">';
    print '<table class="liste centpercent labqc-spec-list-table">';
    print '<colgroup><col class="labqc-spec-col-reference"><col class="labqc-spec-col-product"><col class="labqc-spec-col-version"><col class="labqc-spec-col-status"></colgroup>';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans('LabQCReference').'</th>';
    print '<th>'.$langs->trans('LabQCProduct').'</th>';
    print '<th class="labqc-spec-head-short" style="min-width:90px;white-space:nowrap;word-break:normal">'.$langs->trans('LabQCVersion').'</th>';
    print '<th class="labqc-spec-head-short" style="min-width:90px;white-space:nowrap;word-break:normal">'.$langs->trans('LabQCStatus').'</th>';
    print '</tr>';

    $sql = "SELECT s.rowid, s.ref, s.label, s.version, s.status, p.ref AS product_ref, p.label AS product_label";
    $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_specification AS s";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=s.fk_product";
    $sql .= " WHERE s.entity=".(int) $conf->entity;
    $sql .= " ORDER BY s.rowid DESC";

    $resql = $db->query($sql);
    $count = 0;

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $count++;
            print '<tr class="oddeven">';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCReference')).'"><a href="'.$_SERVER['PHP_SELF'].'?id='.(int) $obj->rowid.'">';
            print dol_escape_htmltag($obj->ref);
            print '</a><br><span class="opacitymedium">'.dol_escape_htmltag($obj->label).'</span></td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCProduct')).'">'.dol_escape_htmltag($obj->product_ref.' - '.$obj->product_label).'</td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCVersion')).'">'.dol_escape_htmltag($obj->version).'</td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCStatus')).'">'.labqcproStatusLabel($obj->status).'</td>';
            print '</tr>';
        }
    }

    if ($count === 0) {
        print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans('LabQCNoSpecifications').'</span></td></tr>';
    }

    print '</table>';
    print '</div>';

    print '</div>';
    print '<div class="clearboth"></div>';
}

print '</div>'; // labqc-spec-page
llxFooter();
$db->close();
