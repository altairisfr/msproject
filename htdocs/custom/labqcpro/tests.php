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
require_once __DIR__.'/lib/labqcpro-inspection.lib.php';
$langs->loadLangs(array('labqcpro@labqcpro'));

if (!labqcproUserCan($user, 'qc', 'read')) {
    accessforbidden($langs->trans('LabQCNoReadPermission'));
}

// Additive, idempotent database setup on first page use (no module reactivation).
if (!labqcproInspectionEnsureSchema($db)) {
    accessforbidden($langs->trans('LabQCInspectionSetupError'));
}

$canCreateTests = labqcproUserCan($user, 'test', 'create');
$canWriteResults = labqcproUserCan($user, 'result', 'write');
$canFinalizeTests = labqcproUserCan($user, 'approval', 'finalize');
$canGenerateDocuments = labqcproUserCan($user, 'document', 'generate');
$canViewAudit = labqcproUserCan($user, 'audit', 'read');

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');

// All mutating operations require a POST with the native Dolibarr CSRF token.
if (in_array($action, array('addtest', 'saveresults', 'finalizetest', 'saveinspection'), true) && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    accessforbidden('POST required for QC mutations');
}


/** Manual inspection metadata. Optional to preserve old analytical-only workflows. */
function labqcproInspectionPostedValues()
{
    $raw = array();
    foreach (array('batch_quantity', 'quantity_inspected', 'aql_level', 'third_party_reference', 'inspection_type') as $field) {
        $raw[$field] = GETPOST($field, 'alphanohtml');
    }
    return $raw;
}

function labqcproTestStatusLabel($status)
{
    global $langs;
    if ((int) $status === 1) return '<span class="badge badge-status4">PASS</span>';
    if ((int) $status === 2) return '<span class="badge badge-status8">FAIL</span>';
    return '<span class="badge badge-status1">'.$langs->trans('LabQCInProgress').'</span>';
}

function labqcproVerdictLabel($verdict)
{
    global $langs;
    if ((int) $verdict === 1) return '<span class="badge badge-status4">PASS</span>';
    if ((int) $verdict === 2) return '<span class="badge badge-status8">FAIL</span>';
    return '<span class="opacitymedium">'.$langs->trans('LabQCPending').'</span>';
}


/**
 * Find an existing CAPA deviation linked to a LabQC test.
 *
 * The integration is optional: no CAPA table is queried unless the module is enabled.
 *
 * @param DoliDB $db Database handler
 * @param int $entity Entity
 * @param int $testId LabQC test rowid
 * @return object|null
 */
function labqcproFindCapaDeviation($db, $entity, $testId)
{
    if (!function_exists('isModEnabled') || !isModEnabled('capadeviationmanager') || $testId <= 0) {
        return null;
    }

    $sql = "SELECT rowid, ref, status FROM ".MAIN_DB_PREFIX."capa_deviation";
    $sql .= " WHERE entity=".(int) $entity;
    $sql .= " AND source_module='labqcpro'";
    $sql .= " AND source_id=".(int) $testId;
    $sql .= " ORDER BY rowid DESC LIMIT 1";

    $resql = $db->query($sql);
    if (!$resql || !$db->num_rows($resql)) {
        return null;
    }

    return $db->fetch_object($resql);
}

/**
 * Build the prefilled CAPA deviation URL for a finalized LabQC FAIL/OOS test.
 *
 * @param object $test LabQC test object enriched with product/specification fields
 * @return string
 */
function labqcproBuildCapaCreateUrl($test)
{
    // Transport only scalar fields. CAPA rebuilds the multiline description
    // server-side, avoiding escaped newline sequences in query strings.
    $params = array(
        'action' => 'create',
        'source_module' => 'labqcpro',
        'source_id' => (int) $test->rowid,
        'source_ref' => (string) $test->ref,
        'title' => 'OOS / QC deviation - '.(string) $test->ref,
        'product_ref' => (string) $test->product_ref,
        'product_label' => (string) $test->product_label,
        'lot_number' => (string) $test->batch_number,
        'spec_ref' => (string) $test->spec_ref,
        'spec_label' => (string) $test->spec_label,
        'spec_version' => (string) $test->spec_version,
        'severity' => 'major',
        'department' => 'Quality Control',
    );

    return dol_buildpath('/capadeviationmanager/deviation/card.php', 1).'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function labqcproCriteriaLabelTest($type, $min, $max, $target, $unit)
{
    $u = $unit !== '' ? ' '.dol_escape_htmltag($unit) : '';
    if ($type === 'range') return dol_escape_htmltag($min).' - '.dol_escape_htmltag($max).$u;
    if ($type === 'max') return '&le; '.dol_escape_htmltag($max).$u;
    if ($type === 'min') return '&ge; '.dol_escape_htmltag($min).$u;
    if ($type === 'equal') return '= '.dol_escape_htmltag($target).$u;
    return dol_escape_htmltag($target);
}

function labqcproEvaluateResult($type, $min, $max, $target, $numraw, $textraw)
{
    if ($type === 'range' || $type === 'max' || $type === 'min') {
        $numraw = trim((string) $numraw);
        if ($numraw === '') return array(null, '', 0);
        $normalized = str_replace(',', '.', $numraw);
        if (!is_numeric($normalized)) return array(null, '', 2);
        $value = (float) $normalized;
        if ($type === 'range') $verdict = ($value >= (float) $min && $value <= (float) $max) ? 1 : 2;
        elseif ($type === 'max') $verdict = ($value <= (float) $max) ? 1 : 2;
        else $verdict = ($value >= (float) $min) ? 1 : 2;
        return array($value, '', $verdict);
    }

    $text = trim((string) $textraw);
    if ($text === '') return array(null, '', 0);
    $verdict = (strcasecmp($text, trim((string) $target)) === 0) ? 1 : 2;
    return array(null, $text, $verdict);
}

function labqcproRecalculateTestStatus($db, $testId, $userId, $entity)
{
    $sql = "SELECT
        SUM(CASE WHEN mandatory = 1 THEN 1 ELSE 0 END) AS mandatory_total,
        SUM(CASE WHEN mandatory = 1 AND verdict = 0 THEN 1 ELSE 0 END) AS mandatory_pending,
        SUM(CASE WHEN mandatory = 1 AND verdict = 2 THEN 1 ELSE 0 END) AS mandatory_fail
        FROM ".MAIN_DB_PREFIX."labqcpro_test_result
        WHERE fk_test=".(int) $testId;
    $resql = $db->query($sql);
    if (!$resql) return false;
    $obj = $db->fetch_object($resql);
    if (!$obj) return false;
    $status = 0;
    if ((int) $obj->mandatory_fail > 0) $status = 2;
    elseif ((int) $obj->mandatory_total > 0 && (int) $obj->mandatory_pending === 0) $status = 1;

    $sql = "UPDATE ".MAIN_DB_PREFIX."labqcpro_test SET status=".(int) $status.",
        fk_user_modif=".(int) $userId." WHERE rowid=".(int) $testId." AND entity=".(int) $entity;
    if (!$db->query($sql)) return false;
    return $status;
}


/**
 * Store an immutable LabQC audit event.
 *
 * @param DoliDB $db Database handler
 * @param int $entity Entity
 * @param int $testId QC test ID
 * @param int $userId User ID
 * @param string $action Audit action
 * @param string $objectType Object type
 * @param int $objectId Related object ID
 * @param string $oldValue Previous value
 * @param string $newValue New value
 * @param string $note Note
 * @return bool
 */
function labqcproAuditEvent($db, $entity, $testId, $userId, $action, $objectType = 'test', $objectId = 0, $oldValue = '', $newValue = '', $note = '')
{
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_audit";
    $sql .= " (entity, fk_test, action, object_type, fk_object, old_value, new_value, note, datec, fk_user)";
    $sql .= " VALUES (";
    $sql .= (int) $entity.",";
    $sql .= (int) $testId.",";
    $sql .= "'".$db->escape($action)."',";
    $sql .= "'".$db->escape($objectType)."',";
    $sql .= (int) $objectId.",";
    $sql .= "'".$db->escape($oldValue)."',";
    $sql .= "'".$db->escape($newValue)."',";
    $sql .= "'".$db->escape($note)."',";
    $sql .= "'".$db->idate(dol_now())."',";
    $sql .= (int) $userId.")";

    return (bool) $db->query($sql);
}

/**
 * Get final approval/lock information for a QC test.
 *
 * @param DoliDB $db Database handler
 * @param int $testId QC test ID
 * @param int $entity Entity
 * @return object|null
 */
function labqcproGetApproval($db, $testId, $entity)
{
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."labqcpro_test_approval";
    $sql .= " WHERE fk_test=".(int) $testId;
    $sql .= " AND entity=".(int) $entity;
    $sql .= " ORDER BY rowid DESC LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        return $db->fetch_object($resql);
    }

    return null;
}


/**
 * Load BatchSafe Pro integration when available.
 *
 * @return bool
 */
function labqcproBatchSafeLoad()
{
    if (!isModEnabled('batchsafepro')) {
        return false;
    }

    if (!class_exists('BatchSafeStatus')) {
        dol_include_once('/batchsafepro/class/batchsafestatus.class.php');
    }

    return class_exists('BatchSafeStatus');
}

/**
 * Return BatchSafe control data for the selected product/lot.
 *
 * @param DoliDB $db Database handler
 * @param int $productId Product ID
 * @param string $batch Batch/lot number
 * @param int $entity Entity
 * @return array<string,mixed>
 */
function labqcproBatchSafeControl($db, $productId, $batch, $entity)
{
    if (!labqcproBatchSafeLoad()) {
        return array(
            'enabled' => false,
            'success' => true,
            'found' => false,
            'status' => '',
            'reason' => '',
            'blocked' => false,
            'error' => '',
        );
    }

    $service = new BatchSafeStatus($db);
    $control = $service->getControlByProductBatch((int) $productId, (string) $batch, (int) $entity);

    if ($control === false) {
        return array(
            'enabled' => true,
            'success' => false,
            'found' => false,
            'status' => '',
            'reason' => '',
            'blocked' => false,
            'error' => $service->error !== '' ? $service->error : 'BatchSafe database lookup failed.',
        );
    }

    $control['success'] = true;
    return $control;
}

/**
 * Synchronize the operational lot status with the LabQC test.
 *
 * Rules:
 * - IN PROGRESS -> Under inspection
 * - While the QC test is OPEN -> Under inspection, regardless of provisional PASS/FAIL
 * - Finalized PASS -> Released
 * - Finalized FAIL/OOS -> Rejected
 *
 * Manual BatchSafe rejection/recall or a non-LabQC quarantine is never
 * overridden automatically.
 *
 * @param DoliDB $db Database handler
 * @param Conf $conf Dolibarr configuration
 * @param User $user Current user
 * @param int $productId Product ID
 * @param string $batch Batch/lot number
 * @param int $qcStatus 0 in progress, 1 pass, 2 fail
 * @param string $testRef QC test reference
 * @return array<string,mixed>
 */
function labqcproSyncBatchSafe($db, $conf, $user, $productId, $batch, $qcStatus, $testRef)
{
    global $langs;

    $control = labqcproBatchSafeControl($db, $productId, $batch, (int) $conf->entity);

    if (empty($control['enabled'])) {
        return array('enabled' => false, 'success' => true, 'changed' => false, 'message' => '');
    }

    if (empty($control['success'])) {
        return array(
            'enabled' => true,
            'success' => false,
            'changed' => false,
            'message' => !empty($control['error']) ? $control['error'] : $langs->trans('LabQCBatchSafeSyncFailed'),
        );
    }

    if (empty($control['found']) || empty($control['lot_id'])) {
        return array(
            'enabled' => true,
            'success' => false,
            'changed' => false,
            'message' => $langs->trans('LabQCBatchSafeLotMissing', $batch),
        );
    }

    $currentStatus = (string) $control['status'];
    $currentReason = trim((string) $control['reason']);
    $labqcManagedReason = ($currentReason !== '' && stripos($currentReason, 'LabQC') === 0);

    $hardManualBlock = in_array($currentStatus, array(
        BatchSafeStatus::STATUS_REJECTED,
        BatchSafeStatus::STATUS_RECALLED,
    ), true);

    $manualQuarantine = (
        $currentStatus === BatchSafeStatus::STATUS_QUARANTINE
        && $currentReason !== ''
        && !$labqcManagedReason
    );

    if ($hardManualBlock || $manualQuarantine) {
        $message = $langs->trans('LabQCBatchSafeHoldPreserved', $currentStatus);
        if ($currentReason !== '') {
            $message .= ' - '.$currentReason;
        }

        return array(
            'enabled' => true,
            'success' => true,
            'changed' => false,
            'warning' => true,
            'message' => $message,
        );
    }

    if ((int) $qcStatus === 1) {
        $targetStatus = BatchSafeStatus::STATUS_RELEASED;
        $reason = $langs->trans('LabQCBatchSafeReasonFinalizedPass', $testRef);
    } elseif ((int) $qcStatus === 2) {
        $targetStatus = BatchSafeStatus::STATUS_REJECTED;
        $reason = $langs->trans('LabQCBatchSafeReasonFinalizedFail', $testRef);
    } else {
        $targetStatus = BatchSafeStatus::STATUS_INSPECTION;
        $reason = $langs->trans('LabQCBatchSafeReasonInProgress', $testRef);
    }

    if ($currentStatus === $targetStatus) {
        return array(
            'enabled' => true,
            'success' => true,
            'changed' => false,
            'message' => $langs->trans('LabQCBatchSafeAlreadyStatus', $targetStatus),
        );
    }

    $service = new BatchSafeStatus($db);
    $result = $service->setLotStatus(
        (int) $control['lot_id'],
        $targetStatus,
        $reason,
        $langs->trans('LabQCBatchSafeAutoSyncNote'),
        $user,
        (int) $conf->entity
    );

    if ($result < 0) {
        return array(
            'enabled' => true,
            'success' => false,
            'changed' => false,
            'message' => $service->error !== '' ? $service->error : $langs->trans('LabQCBatchSafeUpdateFailed'),
        );
    }

    return array(
        'enabled' => true,
        'success' => true,
        'changed' => ($result > 0),
        'message' => $langs->trans('LabQCBatchSafeLotStatus', $targetStatus),
    );
}

/**
 * Render BatchSafe status in the QC test card.
 *
 * @param array<string,mixed> $control BatchSafe control
 * @param Translate $langs Languages
 * @return string
 */
function labqcproBatchSafeBadge($control, $langs)
{
    if (empty($control['enabled'])) {
        return '<span class="opacitymedium">'.$langs->trans('LabQCBatchSafeDisabled').'</span>';
    }

    if (empty($control['success'])) {
        return '<span class="error">'.dol_escape_htmltag($control['error']).'</span>';
    }

    if (empty($control['found'])) {
        return '<span class="warning">'.$langs->trans('LabQCLotNotFoundBatchSafe').'</span>';
    }

    $status = (string) $control['status'];
    $statuses = BatchSafeStatus::getStatuses();
    $label = isset($statuses[$status]) ? $langs->trans($statuses[$status]) : $status;
    $class = !empty($control['blocked']) ? 'badge badge-status1' : 'badge badge-status4';
    if (in_array($status, array(BatchSafeStatus::STATUS_REJECTED, BatchSafeStatus::STATUS_RECALLED), true)) {
        $class = 'badge badge-status8';
    }

    $html = '<span class="'.$class.'">'.dol_escape_htmltag($label).'</span>';
    if (!empty($control['reason'])) {
        $html .= ' <span class="opacitymedium">'.dol_escape_htmltag($control['reason']).'</span>';
    }

    return $html;
}

$tableCheck = $db->query("SELECT 1 FROM ".MAIN_DB_PREFIX."labqcpro_test LIMIT 1");
$resultCheck = $db->query("SELECT 1 FROM ".MAIN_DB_PREFIX."labqcpro_test_result LIMIT 1");

if (!$tableCheck || !$resultCheck) {
    llxHeader('', $langs->trans('LabQCTests'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'" />';
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-inspection.css', 1).'" />';
    print labqcproPageHero($langs->trans('LabQCTests'), $langs->trans('LabQCTestResultsSubtitle'));

// LabQC Pro responsive layout for native and Select2-enhanced Dolibarr controls.
print '<style>
.labqc-test-columns{max-width:100%;}
.labqc-create-card{width:100%;max-width:100%;box-sizing:border-box;border:1px solid rgba(128,128,128,.28);border-radius:3px;overflow:visible;}
.labqc-create-title{padding:10px 12px;box-sizing:border-box;}
.labqc-field-row{display:grid;grid-template-columns:minmax(140px,180px) minmax(0,1fr);gap:10px;align-items:start;padding:8px 12px;box-sizing:border-box;border-top:1px solid rgba(128,128,128,.14);}
.labqc-field-label{font-weight:600;line-height:1.35;padding-top:8px;min-width:0;}
.labqc-field-control{min-width:0;max-width:100%;}
.labqc-field-control select,
.labqc-field-control input[type=text],
.labqc-field-control input[type=date],
.labqc-field-control textarea{display:block!important;float:none!important;position:static!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;box-sizing:border-box!important;}
.labqc-field-control textarea{min-height:88px!important;resize:vertical;}
.labqc-field-control .select2-container{display:block!important;float:none!important;clear:both!important;position:relative!important;left:auto!important;right:auto!important;top:auto!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;}
.labqc-field-control .select2-container .selection{display:block!important;width:100%!important;}
.labqc-field-control .select2-container .select2-selection{width:100%!important;max-width:100%!important;min-height:40px!important;box-sizing:border-box!important;}
.labqc-field-control .select2-container .select2-selection__rendered{line-height:38px!important;box-sizing:border-box!important;}
.labqc-field-control .select2-container .select2-selection__arrow{height:38px!important;}
#labqc_batch_help{display:block!important;clear:both!important;position:static!important;margin:6px 0 0!important;line-height:1.35!important;white-space:normal!important;}
.labqc-create-actions{clear:both;margin-top:14px;margin-bottom:8px;text-align:center;}
.labqc-results-table{table-layout:auto;}
.labqc-results-table .labqc-result-number{width:90px!important;max-width:90px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-results-table .labqc-result-text{width:180px!important;max-width:180px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-results-table .labqc-comment{width:180px!important;max-width:180px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-result-control{display:flex;align-items:center;gap:6px;max-width:100%;}
.labqc-cell-value{min-width:0;max-width:100%;}
@media (max-width: 900px){
  .labqc-test-columns .fichehalfleft,
  .labqc-test-columns .fichehalfright{float:none!important;width:100%!important;max-width:100%!important;margin-left:0!important;margin-right:0!important;padding-left:0!important;padding-right:0!important;}
  .labqc-test-columns .fichehalfright{margin-top:18px!important;}
}
@media (max-width: 767px){
  .labqc-create-card{overflow:visible!important;}
  .labqc-field-row{display:grid!important;grid-template-columns:minmax(0,1fr)!important;gap:5px!important;padding:8px 12px 10px!important;min-height:0!important;height:auto!important;}
  .labqc-field-label{display:block!important;float:none!important;position:static!important;width:100%!important;max-width:100%!important;padding:0!important;margin:0!important;line-height:1.3!important;}
  .labqc-field-control{display:block!important;float:none!important;clear:both!important;position:static!important;width:100%!important;max-width:100%!important;min-width:0!important;height:auto!important;overflow:visible!important;}
  .labqc-field-control textarea{min-height:96px!important;}
  #labqc_batch_help{margin-top:7px!important;margin-bottom:2px!important;}
  .labqc-create-actions{position:static!important;clear:both!important;margin:14px 0 10px!important;}
  .labqc-results-wrap{overflow:visible!important;}
  table.labqc-results-table,
  table.labqc-results-table tbody{display:block!important;width:100%!important;}
  table.labqc-results-table tr.liste_titre{display:none!important;}
  table.labqc-results-table tr.oddeven{display:block!important;width:100%!important;box-sizing:border-box!important;margin:0 0 10px!important;padding:7px 8px!important;border:1px solid rgba(128,128,128,.28)!important;border-radius:4px!important;}
  table.labqc-results-table tr.oddeven td{display:grid!important;grid-template-columns:104px minmax(0,1fr)!important;gap:8px!important;align-items:center!important;width:auto!important;max-width:100%!important;min-width:0!important;padding:6px 3px!important;border:0!important;white-space:normal!important;box-sizing:border-box!important;}
  table.labqc-results-table tr.oddeven td::before{content:attr(data-label);font-weight:600;line-height:1.25;grid-column:1;}
  table.labqc-results-table tr.oddeven td > .labqc-cell-value{grid-column:2;min-width:0;max-width:100%;overflow-wrap:anywhere;}
  .labqc-results-table .labqc-result-number,
  .labqc-results-table .labqc-result-text,
  .labqc-results-table .labqc-comment{width:100%!important;max-width:100%!important;min-width:0!important;}
  .labqc-result-control{width:100%!important;max-width:100%!important;min-width:0!important;}
}
</style>';
    print '<div class="warning">'.$langs->trans('LabQCTestTablesNotInstalled').'</div>';
    llxFooter();
    $db->close();
    exit;
}

if ($action === 'addtest') {
    if (!$canCreateTests) {
        accessforbidden($langs->trans('LabQCNoTestCreatePermission'));
    }
    $fkProduct = GETPOSTINT('fk_product');
    $fkSpecification = GETPOSTINT('fk_specification');
    $ref = trim(GETPOST('ref', 'alphanohtml'));
    $batch = trim(GETPOST('batch_number', 'alphanohtml'));
    $dateTest = trim(GETPOST('date_test', 'alphanohtml'));
    $notes = trim(GETPOST('notes', 'restricthtml'));
    $inspectionError = '';
    $inspectionValues = labqcproInspectionValidate(labqcproInspectionPostedValues(), $inspectionError);

    if ($ref === '') $ref = 'QCT-'.date('Ymd-His');

    $sql = "SELECT rowid, fk_product FROM ".MAIN_DB_PREFIX."labqcpro_specification
        WHERE rowid=".(int) $fkSpecification." AND entity=".(int) $conf->entity;
    $resql = $db->query($sql);
    $spec = $resql ? $db->fetch_object($resql) : null;

    $lotExists = false;
    if ($fkProduct > 0 && $batch !== '') {
        $sqlLot = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_lot";
        $sqlLot .= " WHERE fk_product=".(int) $fkProduct;
        $sqlLot .= " AND batch='".$db->escape($batch)."'";
        $sqlLot .= " AND entity=".(int) $conf->entity;
        $sqlLot .= " ORDER BY rowid DESC LIMIT 1";
        $resLot = $db->query($sqlLot);
        $lotExists = ($resLot && $db->num_rows($resLot) > 0);
        if ($resLot) {
            $db->free($resLot);
        }
    }

    if ($inspectionValues === false) {
        setEventMessages($langs->trans($inspectionError), null, 'errors');
    } elseif (!$spec || $fkProduct <= 0 || (int) $spec->fk_product !== $fkProduct || $batch === '') {
        setEventMessages($langs->trans('LabQCRequiredTestFields'), null, 'errors');
    } elseif (!$lotExists) {
        setEventMessages($langs->trans('LabQCLotDoesNotExist'), null, 'errors');
    } else {
        $dateSql = $dateTest !== '' ? "'".$db->escape($dateTest)."'" : "NULL";
        $db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_test
            (entity, ref, fk_product, fk_specification, batch_number, date_test, status, notes, datec, fk_user_creat)
            VALUES (
            ".((int) $conf->entity).",
            '".$db->escape($ref)."',
            ".((int) $fkProduct).",
            ".((int) $fkSpecification).",
            '".$db->escape($batch)."',
            ".$dateSql.",
            0,
            '".$db->escape($notes)."',
            '".$db->idate(dol_now())."',
            ".((int) $user->id).")";

        if (!$db->query($sql)) {
            $db->rollback();
            setEventMessages($db->lasterror(), null, 'errors');
        } else {
            $newId = $db->last_insert_id(MAIN_DB_PREFIX."labqcpro_test");
            $sql = "SELECT rowid, position, parameter, method, criteria_type, min_value, max_value, target_text, unit, mandatory
                FROM ".MAIN_DB_PREFIX."labqcpro_specification_line
                WHERE fk_specification=".(int) $fkSpecification."
                ORDER BY position ASC, rowid ASC";
            $resLines = $db->query($sql);
            $ok = (bool) $resLines;

            if ($resLines) {
                while ($line = $db->fetch_object($resLines)) {
                    $minSql = $line->min_value === null ? 'NULL' : (string) ((float) $line->min_value);
                    $maxSql = $line->max_value === null ? 'NULL' : (string) ((float) $line->max_value);
                    $sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_test_result
                        (fk_test, fk_specification_line, position, parameter, method, criteria_type, min_value, max_value, target_text, unit, mandatory, verdict, datec, fk_user_creat)
                        VALUES (
                        ".((int) $newId).",
                        ".((int) $line->rowid).",
                        ".((int) $line->position).",
                        '".$db->escape($line->parameter)."',
                        '".$db->escape($line->method)."',
                        '".$db->escape($line->criteria_type)."',
                        ".$minSql.",
                        ".$maxSql.",
                        '".$db->escape($line->target_text)."',
                        '".$db->escape($line->unit)."',
                        ".((int) $line->mandatory).",
                        0,
                        '".$db->idate(dol_now())."',
                        ".((int) $user->id).")";
                    if (!$db->query($sqlInsert)) {
                        $ok = false;
                        break;
                    }
                }
            }

            if ($ok && count(array_filter($inspectionValues, 'strlen')) > 0) {
                $ok = labqcproInspectionPersist($db, (int) $conf->entity, (int) $newId, (int) $user->id, $inspectionValues);
            }

            if ($ok && count(array_filter($inspectionValues, 'strlen')) > 0) {
                foreach ($inspectionValues as $field => $value) {
                    if ($value === '') continue;
                    if (!labqcproAuditEvent(
                        $db, (int) $conf->entity, (int) $newId, (int) $user->id,
                        'inspection_updated', 'inspection', (int) $newId, '', (string) $value, $field
                    )) {
                        $ok = false;
                        break;
                    }
                }
            }

            if ($ok) {
                $ok = labqcproAuditEvent(
                    $db,
                    (int) $conf->entity,
                    (int) $newId,
                    (int) $user->id,
                    'test_created',
                    'test',
                    (int) $newId,
                    '',
                    'IN PROGRESS',
                    'QC test created for lot '.$batch
                );
                if (!$ok) {
                    $db->rollback();
                    setEventMessages($db->lasterror(), null, 'errors');
                } elseif (!$db->commit()) {
                    $db->rollback();
                    setEventMessages($db->lasterror(), null, 'errors');
                } else {

                $sync = labqcproSyncBatchSafe(
                    $db,
                    $conf,
                    $user,
                    $fkProduct,
                    $batch,
                    0,
                    $ref
                );

                if (!empty($sync['enabled']) && (empty($sync['success']) || !empty($sync['warning']))) {
                    setEventMessages($langs->trans('LabQCTestCreatedBatchSafeWarning', $sync['message']), null, 'warnings');
                } elseif (!empty($sync['enabled']) && !empty($sync['message'])) {
                    setEventMessages($sync['message'], null, 'mesgs');
                }

                header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $newId);
                exit;
                } // End successful audit and commit.
            } else {
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            }
        }
    }
}

/**
 * Update manual inspection metadata only on an unfinalized QC test. Results and
 * PASS/FAIL are intentionally unaffected by these contextual fields.
 */
if ($action === 'saveinspection' && $id > 0) {
    if (!$canCreateTests && !$canWriteResults) {
        accessforbidden($langs->trans('LabQCNoTestCreatePermission'));
    }
    $err = '';
    $newInspection = labqcproInspectionValidate(labqcproInspectionPostedValues(), $err);
    if ($newInspection === false) {
        setEventMessages($langs->trans($err), null, 'errors');
    } else {
        $db->begin();
        // Serialize inspection edits against finalization and other edits.
        $sqlLock = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'labqcpro_test';
        $sqlLock .= ' WHERE rowid='.(int) $id.' AND entity='.(int) $conf->entity.' FOR UPDATE';
        $resLock = $db->query($sqlLock);
        if (!$resLock || !$db->num_rows($resLock)) {
            $db->rollback();
            accessforbidden($langs->trans('LabQCTestNotFound'));
        }
        $existingApproval = labqcproGetApproval($db, $id, (int) $conf->entity);
        if ($existingApproval) {
            $db->rollback();
            setEventMessages($langs->trans('LabQCTestAlreadyFinalized'), null, 'errors');
        } else {
            $previousInspection = labqcproInspectionRead($db, (int) $conf->entity, $id);
            $changes = array();
            foreach ($newInspection as $key => $value) {
                if ((string) $previousInspection[$key] !== (string) $value) {
                    $changes[$key] = array((string) $previousInspection[$key], (string) $value);
                }
            }
            $ok = true;
            if (count($changes)) {
                $ok = labqcproInspectionPersist($db, (int) $conf->entity, $id, (int) $user->id, $newInspection);
                if ($ok) {
                    foreach ($changes as $field => $pair) {
                        if (!labqcproAuditEvent($db, (int) $conf->entity, $id, (int) $user->id,
                            'inspection_updated', 'inspection', $id, $pair[0], $pair[1], $field)) {
                            $ok = false;
                            break;
                        }
                    }
                }
            }
            if ($ok) {
                $ok = (bool) $db->commit();
            }
            if ($ok) {
                setEventMessages($langs->trans('LabQCInspectionSaved'), null, 'mesgs');
                header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $id);
                exit;
            }
            $db->rollback();
            setEventMessages($langs->trans('LabQCInspectionSaveFailed'), null, 'errors');
        }
    }
}

if ($action === 'saveresults' && $id > 0) {
    if (!$canWriteResults) {
        accessforbidden($langs->trans('LabQCNoResultPermission'));
    }

    // Child result rows have no entity field. Always verify ownership of the
    // parent test before reading or writing them (also when no approval exists).
    $ownershipSql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'labqcpro_test';
    $ownershipSql .= ' WHERE rowid='.(int) $id.' AND entity='.(int) $conf->entity;
    $ownershipResult = $db->query($ownershipSql);
    if (!$ownershipResult || $db->num_rows($ownershipResult) === 0) {
        accessforbidden($langs->trans('LabQCTestNotFound'));
    }
    $db->free($ownershipResult);

    $approval = labqcproGetApproval($db, $id, (int) $conf->entity);

    if ($approval) {
        setEventMessages($langs->trans('LabQCFinalizedLockedNoModify'), null, 'errors');
    } else {
        $sqlStatus = "SELECT status FROM ".MAIN_DB_PREFIX."labqcpro_test";
        $sqlStatus .= " WHERE rowid=".(int) $id;
        $sqlStatus .= " AND entity=".(int) $conf->entity;
        $resStatus = $db->query($sqlStatus);
        $statusObj = $resStatus ? $db->fetch_object($resStatus) : null;
        if (!$statusObj) {
            accessforbidden($langs->trans('LabQCTestNotFound'));
        }
        $oldQcStatus = (int) $statusObj->status;

        $sql = "SELECT rowid, criteria_type, min_value, max_value, target_text,";
        $sql .= " result_value, result_text, verdict, comment";
        $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_test_result";
        $sql .= " WHERE fk_test=".(int) $id;

        $resql = $db->query($sql);

        if ($resql) {
            $db->begin();
            $ok = true;

            while ($line = $db->fetch_object($resql)) {
                $num = GETPOST('result_value_'.((int) $line->rowid), 'alphanohtml');
                $txt = GETPOST('result_text_'.((int) $line->rowid), 'alphanohtml');
                $comment = GETPOST('comment_'.((int) $line->rowid), 'restricthtml');

                list($value, $text, $verdict) = labqcproEvaluateResult(
                    $line->criteria_type,
                    $line->min_value,
                    $line->max_value,
                    $line->target_text,
                    $num,
                    $txt
                );

                $valueSql = $value === null ? 'NULL' : (string) ((float) $value);

                $oldResultValue = $line->result_value === null ? '' : (string) $line->result_value;
                $newResultValue = $value === null ? '' : (string) $value;
                $oldResultText = (string) $line->result_text;
                $newResultText = (string) $text;
                $oldComment = (string) $line->comment;
                $newComment = (string) $comment;
                $oldVerdict = (int) $line->verdict;
                $newVerdict = (int) $verdict;

                $changed = (
                    $oldResultValue !== $newResultValue
                    || $oldResultText !== $newResultText
                    || $oldComment !== $newComment
                    || $oldVerdict !== $newVerdict
                );

                $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."labqcpro_test_result SET";
                $sqlUpdate .= " result_value=".$valueSql.",";
                $sqlUpdate .= " result_text='".$db->escape($text)."',";
                $sqlUpdate .= " verdict=".(int) $verdict.",";
                $sqlUpdate .= " comment='".$db->escape($comment)."',";
                $sqlUpdate .= " fk_user_modif=".(int) $user->id;
                $sqlUpdate .= " WHERE rowid=".(int) $line->rowid;
                $sqlUpdate .= " AND fk_test=".(int) $id;

                if (!$db->query($sqlUpdate)) {
                    $ok = false;
                    break;
                }

                if ($changed) {
                    $oldSnapshot = json_encode(array(
                        'value' => $oldResultValue,
                        'text' => $oldResultText,
                        'verdict' => $oldVerdict,
                        'comment' => $oldComment,
                    ), JSON_UNESCAPED_UNICODE);

                    $newSnapshot = json_encode(array(
                        'value' => $newResultValue,
                        'text' => $newResultText,
                        'verdict' => $newVerdict,
                        'comment' => $newComment,
                    ), JSON_UNESCAPED_UNICODE);

                    if (!labqcproAuditEvent(
                        $db,
                        (int) $conf->entity,
                        (int) $id,
                        (int) $user->id,
                        'result_updated',
                        'test_result',
                        (int) $line->rowid,
                        (string) $oldSnapshot,
                        (string) $newSnapshot,
                        'Laboratory result modified'
                    )) {
                        $ok = false;
                        break;
                    }
                }
            }

            if ($ok) {
                $newQcStatus = labqcproRecalculateTestStatus($db, $id, $user->id, (int) $conf->entity);
                if ($newQcStatus === false) {
                    $ok = false;
                }

                if ($ok && $oldQcStatus !== (int) $newQcStatus) {
                    $oldLabel = $oldQcStatus === 1 ? 'PASS' : ($oldQcStatus === 2 ? 'FAIL' : 'IN PROGRESS');
                    $newLabel = $newQcStatus === 1 ? 'PASS' : ($newQcStatus === 2 ? 'FAIL' : 'IN PROGRESS');

                    if (!labqcproAuditEvent(
                        $db,
                        (int) $conf->entity,
                        (int) $id,
                        (int) $user->id,
                        'status_changed',
                        'test',
                        (int) $id,
                        $oldLabel,
                        $newLabel,
                        'Automatic PASS / FAIL recalculation'
                    )) {
                        $ok = false;
                    }
                }
            }

            if ($ok) {
                $db->commit();

                $sqlTest = "SELECT ref, fk_product, batch_number FROM ".MAIN_DB_PREFIX."labqcpro_test";
                $sqlTest .= " WHERE rowid=".(int) $id;
                $sqlTest .= " AND entity=".(int) $conf->entity;
                $resTest = $db->query($sqlTest);
                $testForSync = $resTest ? $db->fetch_object($resTest) : null;

                if ($testForSync) {
                    // A provisional PASS/FAIL is an analytical result only.  The lot must
                    // remain under inspection until a user with approval rights finalizes
                    // the QC test.  Finalization performs the actual Release/Reject sync.
                    $sync = labqcproSyncBatchSafe(
                        $db,
                        $conf,
                        $user,
                        (int) $testForSync->fk_product,
                        (string) $testForSync->batch_number,
                        0,
                        (string) $testForSync->ref
                    );

                    if (!empty($sync['enabled']) && (empty($sync['success']) || !empty($sync['warning']))) {
                        setEventMessages($langs->trans('LabQCResultsSavedBatchSafeWarning', $sync['message']), null, 'warnings');
                    } elseif (!empty($sync['enabled']) && !empty($sync['message'])) {
                        setEventMessages($sync['message'], null, 'mesgs');
                    }
                }

                setEventMessages($langs->trans('LabQCResultsSaved'), null, 'mesgs');
            } else {
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            }
        }
    }
}

/*
 * Finalize and lock a completed QC test.
 */
if ($action === 'finalizetest' && $id > 0) {
    if (!$canFinalizeTests) {
        accessforbidden($langs->trans('LabQCNoFinalizePermission'));
    }
    $existingApproval = labqcproGetApproval($db, $id, (int) $conf->entity);

    if ($existingApproval) {
        setEventMessages($langs->trans('LabQCTestAlreadyFinalized'), null, 'warnings');
    } else {
        $sql = "SELECT rowid, ref, fk_product, batch_number, status";
        $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_test";
        $sql .= " WHERE rowid=".(int) $id;
        $sql .= " AND entity=".(int) $conf->entity;
        $resql = $db->query($sql);
        $testToFinalize = ($resql && $db->num_rows($resql)) ? $db->fetch_object($resql) : null;

        if (!$testToFinalize) {
            setEventMessages($langs->trans('LabQCTestNotFound'), null, 'errors');
        } elseif ((int) $testToFinalize->status === 0) {
            setEventMessages($langs->trans('LabQCCompleteMandatoryBeforeFinalize'), null, 'errors');
        } else {
            $approverName = trim($user->getFullName($langs));
            if ($approverName === '') {
                $approverName = trim((string) $user->firstname.' '.(string) $user->lastname);
            }
            $approverPosition = !empty($user->job) ? trim((string) $user->job) : '';

            $db->begin();

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."labqcpro_test_approval";
            $sql .= " (entity, fk_test, decision, fk_user_approval, approver_name, approver_position, approval_date, note, datec)";
            $sql .= " VALUES (";
            $sql .= (int) $conf->entity.",";
            $sql .= (int) $id.",";
            $sql .= (int) $testToFinalize->status.",";
            $sql .= (int) $user->id.",";
            $sql .= "'".$db->escape($approverName)."',";
            $sql .= "'".$db->escape($approverPosition)."',";
            $sql .= "'".$db->idate(dol_now())."',";
            $sql .= "'Finalized and locked from LabQC Pro',";
            $sql .= "'".$db->idate(dol_now())."')";

            $ok = (bool) $db->query($sql);

            if ($ok) {
                $decisionText = ((int) $testToFinalize->status === 1) ? 'PASS' : 'FAIL';
                $ok = labqcproAuditEvent(
                    $db,
                    (int) $conf->entity,
                    (int) $id,
                    (int) $user->id,
                    'test_finalized',
                    'test',
                    (int) $id,
                    'OPEN',
                    'FINALIZED / '.$decisionText,
                    'Results locked; approved by '.$approverName
                );
            }

            if ($ok) {
                $db->commit();

                $sync = labqcproSyncBatchSafe(
                    $db,
                    $conf,
                    $user,
                    (int) $testToFinalize->fk_product,
                    (string) $testToFinalize->batch_number,
                    (int) $testToFinalize->status,
                    (string) $testToFinalize->ref
                );

                if (!empty($sync['enabled']) && (empty($sync['success']) || !empty($sync['warning']))) {
                    setEventMessages($langs->trans('LabQCTestFinalizedBatchSafeWarning', $sync['message']), null, 'warnings');
                } else {
                    setEventMessages($langs->trans('LabQCTestFinalizedSuccess'), null, 'mesgs');
                }

                header('Location: '.$_SERVER['PHP_SELF'].'?id='.(int) $id);
                exit;
            } else {
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            }
        }
    }
}

$test = null;
if ($id > 0) {
    $sql = "SELECT t.*, p.ref AS product_ref, p.label AS product_label,
        s.ref AS spec_ref, s.label AS spec_label, s.version AS spec_version
        FROM ".MAIN_DB_PREFIX."labqcpro_test AS t
        LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=t.fk_product
        LEFT JOIN ".MAIN_DB_PREFIX."labqcpro_specification AS s ON s.rowid=t.fk_specification
        WHERE t.rowid=".(int) $id." AND t.entity=".(int) $conf->entity;
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) $test = $db->fetch_object($resql);
}

$approval = null;
$isFinalized = false;
if ($test) {
    $approval = labqcproGetApproval($db, (int) $test->rowid, (int) $conf->entity);
    $isFinalized = (bool) $approval;
}

$products = array();
$resql = $db->query("SELECT rowid, ref, label FROM ".MAIN_DB_PREFIX."product
    WHERE entity IN (0, ".((int) $conf->entity).") ORDER BY ref ASC");
if ($resql) while ($obj = $db->fetch_object($resql)) $products[] = $obj;

$specifications = array();
$sql = "SELECT s.rowid, s.fk_product, s.ref, s.label, s.version, p.ref AS product_ref, p.label AS product_label
    FROM ".MAIN_DB_PREFIX."labqcpro_specification AS s
    LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=s.fk_product
    WHERE s.entity=".(int) $conf->entity." AND s.status <> 2
    ORDER BY s.rowid DESC";
$resql = $db->query($sql);
if ($resql) while ($obj = $db->fetch_object($resql)) $specifications[] = $obj;

/*
 * Real Dolibarr product lots.
 *
 * The QC test must reference an existing llx_product_lot row.  We also
 * calculate current stock in warehouses belonging to the active entity so
 * the operator can distinguish lots with similar numbers.
 */
$productLots = array();
$sql = "SELECT pl.rowid, pl.fk_product, pl.batch, pl.sellby, pl.eatby,";
$sql .= " COALESCE(SUM(CASE WHEN e.entity=".(int) $conf->entity." THEN pb.qty ELSE 0 END), 0) AS total_qty";
$sql .= " FROM ".MAIN_DB_PREFIX."product_lot AS pl";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=pl.fk_product";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.fk_product=pl.fk_product";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_batch AS pb";
$sql .= " ON pb.fk_product_stock=ps.rowid AND pb.batch=pl.batch";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot AS e ON e.rowid=ps.fk_entrepot";
$sql .= " WHERE pl.entity=".(int) $conf->entity;
$sql .= " AND p.entity IN (0, ".(int) $conf->entity.")";
$sql .= " GROUP BY pl.rowid, pl.fk_product, pl.batch, pl.sellby, pl.eatby";
$sql .= " ORDER BY pl.fk_product ASC, pl.batch ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $productLots[] = $obj;
    }
}

if (labqcproBatchSafeLoad()) {
    $langs->load('batchsafepro@batchsafepro');
}

llxHeader('', $langs->trans('LabQCTests'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'" />';
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-inspection.css', 1).'" />';
print labqcproPageHero($langs->trans('LabQCTests'), $langs->trans('LabQCTestResultsSubtitle'));

// LabQC Pro responsive layout for native and Select2-enhanced Dolibarr controls.
print '<style>
.labqc-test-columns{max-width:100%;}
.labqc-create-card{width:100%;max-width:100%;box-sizing:border-box;border:1px solid rgba(128,128,128,.28);border-radius:3px;overflow:visible;}
.labqc-create-title{padding:10px 12px;box-sizing:border-box;}
.labqc-field-row{display:grid;grid-template-columns:minmax(140px,180px) minmax(0,1fr);gap:10px;align-items:start;padding:8px 12px;box-sizing:border-box;border-top:1px solid rgba(128,128,128,.14);}
.labqc-field-label{font-weight:600;line-height:1.35;padding-top:8px;min-width:0;}
.labqc-field-control{min-width:0;max-width:100%;}
.labqc-field-control select,
.labqc-field-control input[type=text],
.labqc-field-control input[type=date],
.labqc-field-control textarea{display:block!important;float:none!important;position:static!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;box-sizing:border-box!important;}
.labqc-field-control textarea{min-height:88px!important;resize:vertical;}
.labqc-field-control .select2-container{display:block!important;float:none!important;clear:both!important;position:relative!important;left:auto!important;right:auto!important;top:auto!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;}
.labqc-field-control .select2-container .selection{display:block!important;width:100%!important;}
.labqc-field-control .select2-container .select2-selection{width:100%!important;max-width:100%!important;min-height:40px!important;box-sizing:border-box!important;}
.labqc-field-control .select2-container .select2-selection__rendered{line-height:38px!important;box-sizing:border-box!important;}
.labqc-field-control .select2-container .select2-selection__arrow{height:38px!important;}
#labqc_batch_help{display:block!important;clear:both!important;position:static!important;margin:6px 0 0!important;line-height:1.35!important;white-space:normal!important;}
.labqc-create-actions{clear:both;margin-top:14px;margin-bottom:8px;text-align:center;}
.labqc-results-table{table-layout:auto;}
.labqc-results-table .labqc-result-number{width:90px!important;max-width:90px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-results-table .labqc-result-text{width:180px!important;max-width:180px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-results-table .labqc-comment{width:180px!important;max-width:180px!important;min-width:0!important;box-sizing:border-box!important;}
.labqc-result-control{display:flex;align-items:center;gap:6px;max-width:100%;}
.labqc-cell-value{min-width:0;max-width:100%;}
@media (max-width: 900px){
  .labqc-test-columns .fichehalfleft,
  .labqc-test-columns .fichehalfright{float:none!important;width:100%!important;max-width:100%!important;margin-left:0!important;margin-right:0!important;padding-left:0!important;padding-right:0!important;}
  .labqc-test-columns .fichehalfright{margin-top:18px!important;}
}
@media (max-width: 767px){
  .labqc-create-card{overflow:visible!important;}
  .labqc-field-row{display:grid!important;grid-template-columns:minmax(0,1fr)!important;gap:5px!important;padding:8px 12px 10px!important;min-height:0!important;height:auto!important;}
  .labqc-field-label{display:block!important;float:none!important;position:static!important;width:100%!important;max-width:100%!important;padding:0!important;margin:0!important;line-height:1.3!important;}
  .labqc-field-control{display:block!important;float:none!important;clear:both!important;position:static!important;width:100%!important;max-width:100%!important;min-width:0!important;height:auto!important;overflow:visible!important;}
  .labqc-field-control textarea{min-height:96px!important;}
  #labqc_batch_help{margin-top:7px!important;margin-bottom:2px!important;}
  .labqc-create-actions{position:static!important;clear:both!important;margin:14px 0 10px!important;}
  .labqc-results-wrap{overflow:visible!important;}
  table.labqc-results-table,
  table.labqc-results-table tbody{display:block!important;width:100%!important;}
  table.labqc-results-table tr.liste_titre{display:none!important;}
  table.labqc-results-table tr.oddeven{display:block!important;width:100%!important;box-sizing:border-box!important;margin:0 0 10px!important;padding:7px 8px!important;border:1px solid rgba(128,128,128,.28)!important;border-radius:4px!important;}
  table.labqc-results-table tr.oddeven td{display:grid!important;grid-template-columns:104px minmax(0,1fr)!important;gap:8px!important;align-items:center!important;width:auto!important;max-width:100%!important;min-width:0!important;padding:6px 3px!important;border:0!important;white-space:normal!important;box-sizing:border-box!important;}
  table.labqc-results-table tr.oddeven td::before{content:attr(data-label);font-weight:600;line-height:1.25;grid-column:1;}
  table.labqc-results-table tr.oddeven td > .labqc-cell-value{grid-column:2;min-width:0;max-width:100%;overflow-wrap:anywhere;}
  .labqc-results-table .labqc-result-number,
  .labqc-results-table .labqc-result-text,
  .labqc-results-table .labqc-comment{width:100%!important;max-width:100%!important;min-width:0!important;}
  .labqc-result-control{width:100%!important;max-width:100%!important;min-width:0!important;}
}
</style>';
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-refresh.css', 1).'?v=1.1.1" />';
print '<div class="labqc-tests-refresh">';

if ($test) {
    print '<div class="tabsAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans('LabQCNewListTests').'</a></div>';

    print '<section class="labqc-test-summary">';
    print '<table class="border centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans('LabQCTest').' '.dol_escape_htmltag($test->ref).'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('LabQCProduct').'</td><td>'.dol_escape_htmltag($test->product_ref.' - '.$test->product_label).'</td></tr>';
    print '<tr><td>'.$langs->trans('LabQCSpecification').'</td><td>'.dol_escape_htmltag($test->spec_ref.' - '.$test->spec_label.' v'.$test->spec_version).'</td></tr>';
    print '<tr><td>'.$langs->trans('LabQCBatchLot').'</td><td>'.dol_escape_htmltag($test->batch_number).'</td></tr>';
    print '<tr><td>'.$langs->trans('LabQCStatus').'</td><td>'.labqcproTestStatusLabel($test->status).'</td></tr>';

    if ($isFinalized) {
        $decisionLabel = ((int) $approval->decision === 1) ? 'PASS' : 'FAIL';
        print '<tr><td>'.$langs->trans('LabQCFinalization').'</td><td><span class="badge badge-status4">'.$langs->trans('LabQCFinalized').'</span>';
        print ' <span class="opacitymedium">'.dol_escape_htmltag($approval->approver_name);
        if (!empty($approval->approver_position)) {
            print ' - '.dol_escape_htmltag($approval->approver_position);
        }
        $approvalDateDisplay = trim((string) $approval->approval_date);
        if ($approvalDateDisplay !== '') {
            $approvalTimestamp = $db->jdate($approvalDateDisplay);
            if (!empty($approvalTimestamp)) {
                $approvalDateDisplay = dol_print_date($approvalTimestamp, '%d.%m.%Y %H:%M', 'tzuser');
            }
        }
        print ' | '.dol_escape_htmltag($approvalDateDisplay).' | '.$decisionLabel.'</span></td></tr>';
    } else {
        print '<tr><td>'.$langs->trans('LabQCFinalization').'</td><td><span class="badge badge-status1">'.$langs->trans('LabQCOpen').'</span></td></tr>';
    }

    // Optional CAPA & Deviation Manager integration for finalized FAIL/OOS tests.
    if (function_exists('isModEnabled') && isModEnabled('capadeviationmanager') && (int) $test->status === 2) {
        $linkedCapa = labqcproFindCapaDeviation($db, (int) $conf->entity, (int) $test->rowid);

        print '<tr><td>'.$langs->trans('LabQCCAPADeviation').'</td><td>';

        if ($linkedCapa) {
            if ($user->admin || $user->hasRight('capadeviationmanager', 'read')) {
                $capaUrl = dol_buildpath('/capadeviationmanager/deviation/card.php', 1).'?id='.(int) $linkedCapa->rowid;
                print '<a class="butAction" href="'.dol_escape_htmltag($capaUrl).'">'.$langs->trans('LabQCOpenCAPADeviation').' '.dol_escape_htmltag($linkedCapa->ref).'</a>';
            } else {
                print '<span class="opacitymedium">'.$langs->trans('LabQCCAPALinkedNoPermission', dol_escape_htmltag($linkedCapa->ref)).'</span>';
            }
        } elseif ($isFinalized) {
            if ($user->admin || $user->hasRight('capadeviationmanager', 'write')) {
                $capaCreateUrl = labqcproBuildCapaCreateUrl($test);
                print '<a class="butAction" href="'.dol_escape_htmltag($capaCreateUrl).'">'.$langs->trans('LabQCCreateCAPADeviation').'</a>';
                print ' <span class="opacitymedium">'.$langs->trans('LabQCCAPAPrefillHint').'</span>';
            } else {
                print '<span class="opacitymedium">'.$langs->trans('LabQCCAPANoCreatePermission').'</span>';
            }
        } else {
            print '<span class="opacitymedium">'.$langs->trans('LabQCCAPAFinalizeFirst').'</span>';
        }

        print '</td></tr>';
    }

    print '<tr><td>'.$langs->trans('LabQCAudit').'</td><td>';
    if ($canViewAudit) {
        print '<a href="'.dol_buildpath('/labqcpro/audit.php', 1).'?testid='.(int) $test->rowid.'">'.$langs->trans('LabQCViewAuditTrail').'</a>';
    } else {
        print '<span class="opacitymedium">'.$langs->trans('LabQCNoAuditPermissionShort').'</span>';
    }
    print '</td></tr>';

    $batchSafeControl = labqcproBatchSafeControl(
        $db,
        (int) $test->fk_product,
        (string) $test->batch_number,
        (int) $conf->entity
    );
    print '<tr><td>'.$langs->trans('LabQCBatchSafePro').'</td><td>'.labqcproBatchSafeBadge($batchSafeControl, $langs);
    print '<div class="labqc-batchsafe-context">' .dol_escape_htmltag($langs->trans('LabQCBatchSafeCurrentState')). '</div>';
    print '</td></tr>';

    print '</table></section>';

    // A separate inspection panel keeps the analytical results workflow unchanged.
    $inspection = labqcproInspectionRead($db, (int) $conf->entity, (int) $test->rowid);
    $mayEditInspection = !$isFinalized && ($canCreateTests || $canWriteResults);
    print '<section class="labqc-inspection-panel">';
    print '<div class="labqc-results-heading">'.$langs->trans('LabQCInspectionDetails').'</div>';
    if ($mayEditInspection) {
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $test->rowid.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="saveinspection">';
        print '<input type="hidden" name="id" value="'.(int) $test->rowid.'">';
    }
    print '<div class="labqc-inspection-grid">';
    foreach (array('batch_quantity' => 'LabQCBatchQuantity', 'quantity_inspected' => 'LabQCQuantityInspected', 'aql_level' => 'LabQCAqlLevel', 'third_party_reference' => 'LabQCThirdPartyReference', 'inspection_type' => 'LabQCInspectionType') as $key => $translation) {
        print '<div class="labqc-inspection-field"><label>'.dol_escape_htmltag($langs->trans($translation)).'</label>';
        if ($mayEditInspection) {
            if ($key === 'inspection_type') {
                print '<select class="flat" name="inspection_type"><option value="">'.$langs->trans('LabQCSelectOptional').'</option>';
                foreach (array('incoming', 'preshipment', 'other') as $inspectionType) {
                    print '<option value="'.$inspectionType.'"'.($inspection[$key] === $inspectionType ? ' selected' : '').'>'.dol_escape_htmltag(labqcproInspectionTypeLabel($inspectionType, $langs)).'</option>';
                }
                print '</select>';
            } else {
                $inputmode = in_array($key, array('batch_quantity','quantity_inspected','aql_level'), true) ? ' inputmode="decimal"' : '';
                print '<input class="flat" type="text" name="'.$key.'"'.$inputmode.' maxlength="255" value="'.dol_escape_htmltag($inspection[$key]).'">';
            }
        } else {
            $value = $key === 'inspection_type' ? labqcproInspectionTypeLabel($inspection[$key], $langs) : $inspection[$key];
            print '<div class="labqc-inspection-readonly">'.($value === '' ? '—' : dol_escape_htmltag($value)).'</div>';
        }
        print '</div>';
    }
    print '</div>';
    if ($mayEditInspection) {
        print '<div class="opacitymedium small">'.$langs->trans('LabQCAqlManualHint').'</div>';
        print '<div class="center labqc-inspection-actions"><input type="submit" class="button button-save" value="'.$langs->trans('LabQCSaveInspectionDetails').'"></div>';
        print '</form>';
    }
    print '</section>';

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $test->rowid.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="saveresults">';
    print '<input type="hidden" name="id" value="'.(int) $test->rowid.'">';

    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."labqcpro_test_result
        WHERE fk_test=".(int) $test->rowid." ORDER BY position ASC, rowid ASC";
    $resql = $db->query($sql);

    print '<section class="labqc-test-results-panel"><div class="labqc-results-heading">'.$langs->trans('LabQCResult').'</div>';
    print '<div class="div-table-responsive labqc-results-wrap"><table class="liste centpercent labqc-results-table">';
    print '<tr class="liste_titre"><th>'.$langs->trans('LabQCParameter').'</th><th>'.$langs->trans('LabQCMethod').'</th><th>'.$langs->trans('LabQCSpecification').'</th><th>'.$langs->trans('LabQCResult').'</th><th>'.$langs->trans('LabQCVerdict').'</th><th>'.$langs->trans('LabQCComment').'</th></tr>';

    $count = 0;
    if ($resql) {
        while ($line = $db->fetch_object($resql)) {
            $count++;
            print '<tr class="oddeven">';
            print '<td data-label="'.$langs->trans('LabQCParameter').'"><div class="labqc-cell-value"><strong>'.dol_escape_htmltag($line->parameter).'</strong>'.((int)$line->mandatory ? ' <span class="opacitymedium">'.$langs->trans('LabQCMandatory').'</span>' : '').'</div></td>';
            print '<td data-label="'.$langs->trans('LabQCMethod').'"><div class="labqc-cell-value">'.dol_escape_htmltag($line->method).'</div></td>';
            print '<td data-label="'.$langs->trans('LabQCSpecification').'"><div class="labqc-cell-value">'.labqcproCriteriaLabelTest($line->criteria_type, $line->min_value, $line->max_value, $line->target_text, $line->unit).'</div></td>';
            print '<td data-label="'.$langs->trans('LabQCResult').'"><div class="labqc-cell-value">';
            if ($line->criteria_type === 'range' || $line->criteria_type === 'max' || $line->criteria_type === 'min') {
                $v = $line->result_value === null ? '' : $line->result_value;
                if ($isFinalized || !$canWriteResults) {
                    print dol_escape_htmltag($v);
                } else {
                    print '<div class="labqc-result-control"><input class="flat labqc-result-number" type="text" name="result_value_'.(int)$line->rowid.'" value="'.dol_escape_htmltag($v).'">';
                }
                if ($line->unit !== '') print ' <span class="labqc-result-unit">'.dol_escape_htmltag($line->unit).'</span>';
                if (!$isFinalized && $canWriteResults) print '</div>';
            } else {
                if ($isFinalized || !$canWriteResults) {
                    print dol_escape_htmltag($line->result_text);
                } else {
                    print '<input class="flat labqc-result-text" type="text" name="result_text_'.(int)$line->rowid.'" value="'.dol_escape_htmltag($line->result_text).'">';
                }
            }
            print '</div></td>';
            print '<td data-label="'.$langs->trans('LabQCVerdict').'"><div class="labqc-cell-value">'.labqcproVerdictLabel($line->verdict).'</div></td>';
            if ($isFinalized || !$canWriteResults) {
                print '<td data-label="'.$langs->trans('LabQCComment').'"><div class="labqc-cell-value">'.dol_escape_htmltag($line->comment).'</div></td>';
            } else {
                print '<td data-label="'.$langs->trans('LabQCComment').'"><div class="labqc-cell-value"><input class="flat labqc-comment" type="text" name="comment_'.(int)$line->rowid.'" value="'.dol_escape_htmltag($line->comment).'"></div></td>';
            }
            print '</tr>';
        }
    }

    if ($count === 0) print '<tr><td colspan="6">'.$langs->trans('LabQCNoParametersLoaded').'</td></tr>';
    print '</table></div></section>';

    if ($count > 0 && !$isFinalized && $canWriteResults) {
        print '<div class="center"><input class="button button-save" type="submit" value="'.$langs->trans('LabQCSaveResults').'"></div>';
    }
    print '</form>';

    if ($isFinalized) {
        print '<div class="info center">'.$langs->trans('LabQCFinalizedResultsLocked').'</div>';
    } else {
        if (!$canWriteResults) {
            print '<div class="info center">'.$langs->trans('LabQCReadOnlyResults').'</div>';
        }

        if ((int) $test->status !== 0) {
            if ($canFinalizeTests) {
                print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.(int) $test->rowid.'" class="center">';
                print '<input type="hidden" name="token" value="'.newToken().'">';
                print '<input type="hidden" name="action" value="finalizetest">';
                print '<input type="hidden" name="id" value="'.(int) $test->rowid.'">';
                print '<input class="button" type="submit" value="'.$langs->trans('LabQCFinalizeLockTest').'" onclick="return confirm(\''.dol_escape_js($langs->trans('LabQCFinalizeConfirm')).'\');">';
                print '</form>';
            } else {
                print '<div class="opacitymedium center">'.$langs->trans('LabQCReadyApprovalRequired').'</div>';
            }
        } else {
            print '<div class="opacitymedium center">'.$langs->trans('LabQCCompleteMandatory').'</div>';
        }
    }
} else {
    print '<div class="fichecenter labqc-test-columns"><div class="fichehalfleft">';

    if ($canCreateTests) {
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="addtest">';

    print '<div class="labqc-create-card">';
    print '<div class="liste_titre labqc-create-title">'.$langs->trans('LabQCCreateTest').'</div>';

    print '<div class="labqc-field-row"><div class="labqc-field-label fieldrequired">'.$langs->trans('LabQCProduct').'</div><div class="labqc-field-control"><select class="flat minwidth300" id="labqc_product" name="fk_product" required>';
    print '<option value="">'.$langs->trans('LabQCSelectProduct').'</option>';
    foreach ($products as $p) print '<option value="'.(int)$p->rowid.'">'.dol_escape_htmltag($p->ref.' - '.$p->label).'</option>';
    print '</select></div></div>';

    print '<div class="labqc-field-row"><div class="labqc-field-label fieldrequired">'.$langs->trans('LabQCSpecification').'</div><div class="labqc-field-control"><select class="flat minwidth400" id="labqc_specification" name="fk_specification" required disabled>';
    print '<option value="">'.$langs->trans('LabQCSelectProductFirst').'</option>';
    foreach ($specifications as $s) {
        print '<option value="'.(int)$s->rowid.'" data-product="'.(int)$s->fk_product.'" hidden disabled>';
        print dol_escape_htmltag($s->product_ref.' | '.$s->ref.' | '.$s->label.' | v'.$s->version);
        print '</option>';
    }
    print '</select></div></div>';

    print '<div class="labqc-field-row"><div class="labqc-field-label">'.$langs->trans('LabQCTestReference').'</div><div class="labqc-field-control"><input class="flat minwidth200" type="text" name="ref" placeholder="'.$langs->trans('LabQCAutoIfEmpty').'"></div></div>';

    print '<div class="labqc-field-row"><div class="labqc-field-label fieldrequired">'.$langs->trans('LabQCBatchLot').'</div><div class="labqc-field-control">';
    print '<select class="flat minwidth300" id="labqc_batch" name="batch_number" required disabled>';
    print '<option value="">'.$langs->trans('LabQCSelectProductFirst').'</option>';
    foreach ($productLots as $lot) {
        $expiry = '';
        if (!empty($lot->eatby)) {
            $expiry = $lot->eatby;
        } elseif (!empty($lot->sellby)) {
            $expiry = $lot->sellby;
        }

        $label = (string) $lot->batch.' | stock: '.price($lot->total_qty);
        if ($expiry !== '') {
            $label .= ' | exp: '.$expiry;
        }

        print '<option value="'.dol_escape_htmltag($lot->batch).'" data-product="'.(int)$lot->fk_product.'" hidden disabled>';
        print dol_escape_htmltag($label);
        print '</option>';
    }
    print '</select>';
    print '<div class="opacitymedium small" id="labqc_batch_help">'.$langs->trans('LabQCExistingLotsOnly').'</div>';
    print '</div></div>';
    print '<div class="labqc-field-row"><div class="labqc-field-label">'.$langs->trans('LabQCTestDate').'</div><div class="labqc-field-control"><input class="flat" type="date" name="date_test" value="'.date('Y-m-d').'"></div></div>';
    print '<div class="labqc-field-row"><div class="labqc-field-label">'.$langs->trans('LabQCInspectionType').'</div><div class="labqc-field-control"><select class="flat" name="inspection_type">';
    print '<option value="">'.$langs->trans('LabQCSelectOptional').'</option>';
    foreach (array('incoming', 'preshipment', 'other') as $inspectionType) {
        print '<option value="'.$inspectionType.'">'.dol_escape_htmltag(labqcproInspectionTypeLabel($inspectionType, $langs)).'</option>';
    }
    print '</select></div></div>';
    foreach (array('batch_quantity' => 'LabQCBatchQuantity', 'quantity_inspected' => 'LabQCQuantityInspected', 'aql_level' => 'LabQCAqlLevel', 'third_party_reference' => 'LabQCThirdPartyReference') as $name => $translation) {
        $inputmode = in_array($name, array('batch_quantity', 'quantity_inspected', 'aql_level'), true) ? ' inputmode="decimal"' : '';
        print '<div class="labqc-field-row"><div class="labqc-field-label">'.$langs->trans($translation).'</div><div class="labqc-field-control">';
        print '<input class="flat" type="text" name="'.$name.'"'.$inputmode.' maxlength="255" value=""></div></div>';
    }
    print '<div class="labqc-field-row"><div class="labqc-field-label"></div><div class="labqc-field-control opacitymedium small">'.$langs->trans('LabQCAqlManualHint').'</div></div>';
    print '<div class="labqc-field-row"><div class="labqc-field-label">'.$langs->trans('LabQCNotes').'</div><div class="labqc-field-control"><textarea class="flat centpercent" rows="3" name="notes"></textarea></div></div>';
    print '</div>';

    print '<div class="labqc-create-actions"><input class="button button-save" type="submit" value="'.$langs->trans('LabQCCreateTest').'"></div>';
    print '</form>';

    print '<script>
    document.addEventListener("DOMContentLoaded", function () {
        var product = document.getElementById("labqc_product");
        var specification = document.getElementById("labqc_specification");
        var batch = document.getElementById("labqc_batch");
        var help = document.getElementById("labqc_batch_help");

        function filterLinkedSelect(select, productId, emptyText) {
            if (!select) return 0;

            var visible = 0;
            select.value = "";

            for (var i = 1; i < select.options.length; i++) {
                var option = select.options[i];
                var matches = productId !== "" && option.getAttribute("data-product") === productId;
                option.hidden = !matches;
                option.disabled = !matches;
                if (matches) visible++;
            }

            select.disabled = productId === "" || visible === 0;
            select.options[0].text = productId === "" ? '.json_encode(html_entity_decode($langs->trans('LabQCSelectProductFirst'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).' : (visible ? emptyText : '.json_encode(html_entity_decode($langs->trans('LabQCNoMatchingRecords'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).');

            return visible;
        }

        function refreshLinkedFields() {
            var productId = product ? product.value : "";
            filterLinkedSelect(specification, productId, '.json_encode(html_entity_decode($langs->trans('LabQCSelectSpecification'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).');
            var lotCount = filterLinkedSelect(batch, productId, '.json_encode(html_entity_decode($langs->trans('LabQCSelectExistingBatchLot'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).');

            if (help) {
                if (productId === "") {
                    help.textContent = '.json_encode(html_entity_decode($langs->trans('LabQCSelectProductLoadLots'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).';
                } else if (lotCount === 0) {
                    help.textContent = '.json_encode(html_entity_decode($langs->trans('LabQCNoLotsForSelectedProduct'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).';
                } else {
                    help.textContent = '.json_encode(html_entity_decode($langs->trans('LabQCExistingLotsOnly'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).';
                }
            }
        }

        if (product) {
            product.addEventListener("change", refreshLinkedFields);
            refreshLinkedFields();
        }
    });
    </script>';
    } else {
        print '<div class="info">'.$langs->trans('LabQCReadOnlyTests').'</div>';
    }

    print '</div>';

    print '<div class="fichehalfright"><div class="div-table-responsive"><table class="liste centpercent labqc-test-list-table">';
    print '<tr class="liste_titre"><th>'.$langs->trans('LabQCReference').'</th><th>'.$langs->trans('LabQCProduct').'</th><th>'.$langs->trans('LabQCBatchLot').'</th><th>'.$langs->trans('LabQCStatus').'</th></tr>';

    $sql = "SELECT t.rowid, t.ref, t.batch_number, t.status, p.ref AS product_ref, p.label AS product_label
        FROM ".MAIN_DB_PREFIX."labqcpro_test AS t
        LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=t.fk_product
        WHERE t.entity=".(int)$conf->entity." ORDER BY t.rowid DESC";
    $resql = $db->query($sql);
    $count = 0;
    if ($resql) {
        while ($o = $db->fetch_object($resql)) {
            $count++;
            print '<tr class="oddeven"><td data-label="'.dol_escape_htmltag($langs->trans('LabQCReference')).'"><span class="labqc-test-list-value"><a href="'.$_SERVER['PHP_SELF'].'?id='.(int)$o->rowid.'">'.dol_escape_htmltag($o->ref).'</a></span></td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCProduct')).'"><span class="labqc-test-list-value">'.dol_escape_htmltag($o->product_ref.' - '.$o->product_label).'</span></td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCBatchLot')).'"><span class="labqc-test-list-value">'.dol_escape_htmltag($o->batch_number).'</span></td>';
            print '<td data-label="'.dol_escape_htmltag($langs->trans('LabQCStatus')).'"><span class="labqc-test-list-value">'.labqcproTestStatusLabel($o->status).'</span></td></tr>';
        }
    }
    if ($count === 0) print '<tr><td colspan="4">'.$langs->trans('LabQCNoTests').'</td></tr>';
    print '</table></div></div></div><div class="clearboth"></div>';
}

print '</div>'; // labqc-tests-refresh
llxFooter();
$db->close();
