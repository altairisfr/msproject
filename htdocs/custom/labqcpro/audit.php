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
 * LabQC Pro - Audit Trail
 * Copyright (C) 2026 PanciucSoft
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

if (!labqcproUserCan($user, 'audit', 'read')) {
    accessforbidden($langs->trans('LabQCNoAuditPermission'));
}

$testId = GETPOSTINT('testid');

/**
 * Convert the technical JSON snapshot stored in the immutable audit log
 * into a readable laboratory result summary.
 *
 * Raw JSON remains unchanged in the database for traceability.
 *
 * @param string $raw Raw audit value
 * @param string $objectType Audit object type
 * @param string $action Audit action
 * @param string $unit Result unit
 * @return string
 */
function labqcproFormatAuditValue($raw, $objectType, $action, $unit = '')
{
    global $langs;
    $raw = (string) $raw;

    if ($raw === '') {
        return '—';
    }

    if ($objectType !== 'test_result' || $action !== 'result_updated') {
        $statusMap = array(
            'IN PROGRESS' => $langs->trans('LabQCInProgress'),
            'OPEN' => $langs->trans('LabQCOpen'),
            'FINALIZED / PASS' => $langs->trans('LabQCFinalized').' / PASS',
            'FINALIZED / FAIL' => $langs->trans('LabQCFinalized').' / FAIL',
        );
        return isset($statusMap[$raw]) ? $statusMap[$raw] : $raw;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return $raw;
    }

    $result = '—';

    if (isset($data['value']) && $data['value'] !== '') {
        $result = (string) $data['value'];
        if ((string) $unit !== '') {
            $result .= ' '.(string) $unit;
        }
    } elseif (isset($data['text']) && $data['text'] !== '') {
        $result = (string) $data['text'];
    }

    $verdictMap = array(
        0 => $langs->trans('LabQCPending'),
        1 => 'PASS',
        2 => 'FAIL',
    );

    $verdictCode = isset($data['verdict']) ? (int) $data['verdict'] : 0;
    $verdict = isset($verdictMap[$verdictCode]) ? $verdictMap[$verdictCode] : $langs->trans('LabQCPending');

    // Dolibarr may HTML-encode translated substitution parameters.
    // Decode once here because the final value is escaped safely at output time.
    $parts = array(
        html_entity_decode($langs->trans('LabQCAuditResultPrefix', $result), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        html_entity_decode($langs->trans('LabQCAuditVerdictPrefix', $verdict), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    );

    if (!empty($data['comment'])) {
        $parts[] = html_entity_decode($langs->trans('LabQCAuditCommentPrefix', (string) $data['comment']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return implode(' | ', $parts);
}


/**
 * Translate standard immutable audit notes for display without changing stored history.
 *
 * @param string $note Stored note
 * @return string
 */
function labqcproAuditNoteLabel($note)
{
    global $langs;
    $note = (string) $note;

    $inspectionNames = array('batch_quantity' => 'LabQCBatchQuantity', 'quantity_inspected' => 'LabQCQuantityInspected', 'aql_level' => 'LabQCAqlLevel', 'third_party_reference' => 'LabQCThirdPartyReference', 'inspection_type' => 'LabQCInspectionType');
    if (isset($inspectionNames[$note])) return $langs->trans($inspectionNames[$note]);

    if ($note === 'Laboratory result modified') {
        return $langs->trans('LabQCResultModifiedNote');
    }
    if ($note === 'Automatic PASS / FAIL recalculation') {
        return $langs->trans('LabQCAutomaticRecalculationNote');
    }
    if ($note === 'Finalized and locked from LabQC Pro') {
        return $langs->trans('LabQCFinalizedLockedNote');
    }
    if (strpos($note, 'Results locked; approved by ') === 0) {
        return $langs->trans('LabQCResultsLockedApprovedBy', substr($note, strlen('Results locked; approved by ')));
    }
    if (strpos($note, 'QC test created for lot ') === 0) {
        return $langs->trans('LabQCTestCreatedForLot', substr($note, strlen('QC test created for lot ')));
    }

    return $note;
}

/**
 * Render a readable audit action label using Dolibarr-native badges.
 * The immutable technical action code remains unchanged in the database.
 *
 * @param string $action Technical audit action code
 * @return string Safe HTML
 */
function labqcproAuditActionBadge($action)
{
    global $langs;
    $action = (string) $action;

    $map = array(
        'test_created' => array($langs->trans('LabQCAuditCreated'), 'labqc-action-created'),
        'result_updated' => array($langs->trans('LabQCAuditResultUpdated'), 'labqc-action-result'),
        'inspection_updated' => array($langs->trans('LabQCAuditInspectionUpdated'), 'labqc-action-result'),
        'status_changed' => array($langs->trans('LabQCAuditStatusChanged'), 'labqc-action-status'),
        'test_finalized' => array($langs->trans('LabQCAuditFinalized'), 'labqc-action-finalized'),
    );

    if (isset($map[$action])) {
        $label = $map[$action][0];
        $badgeClass = $map[$action][1];
    } else {
        $label = ucfirst(str_replace('_', ' ', $action));
        $badgeClass = 'labqc-action-created';
    }

    return '<span class="badge '.dol_escape_htmltag($badgeClass).' labqc-audit-action-badge"'
        .' title="'.dol_escape_htmltag($langs->trans('LabQCTechnicalAction', $action)).'">'
        .dol_escape_htmltag($label)
        .'</span>';
}

llxHeader('', $langs->trans('LabQCAuditTrail'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'" />';
print labqcproPageHero($langs->trans('LabQCAuditTrail'), $langs->trans('LabQCAuditSubtitle'));

if ($testId > 0) {
    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.dol_buildpath('/labqcpro/tests.php', 1).'?id='.(int) $testId.'">'.$langs->trans('LabQCBackToTest').'</a>';
    print '</div>';
}

print '<style>';
print '.labqc-audit-action-badge { white-space: nowrap; font-weight: 600; border: 0; color: #fff !important; }';
print '.labqc-action-created { background: #2f6fbb !important; }';
print '.labqc-action-result { background: #6f42c1 !important; }';
print '.labqc-action-status { background: #c58a00 !important; }';
print '.labqc-action-finalized { background: #218838 !important; }';
print '@media (max-width: 767px) {';
print '  .labqc-audit-wrap { overflow: visible !important; }';
print '  table.labqc-audit-table { display: block; width: 100%; border-collapse: separate; }';
print '  .labqc-audit-table thead { display: none; }';
print '  .labqc-audit-table tbody { display: flex !important; flex-direction: column; width: 100%; gap: 12px; }';
print '  .labqc-audit-table tr.oddeven { display: flex !important; flex-direction: column; width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; margin: 0; padding: 6px 0; background: #fff; overflow: hidden; }';
print '  .labqc-audit-table td { display: flex !important; align-items: flex-start; width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box; padding: 6px 10px !important; border: 0 !important; white-space: normal !important; overflow: visible !important; line-height: 1.35; }';
print '  .labqc-audit-table td::before { content: attr(data-label); flex: 0 0 100px; width: 100px; min-width: 100px; padding-right: 8px; box-sizing: border-box; font-weight: 600; color: inherit; }';
print '  .labqc-audit-table .labqc-audit-value { display: block; flex: 1 1 auto; min-width: 0; max-width: 100%; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; hyphens: auto; line-height: 1.35; }';
print '  .labqc-audit-table .labqc-audit-value a { white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; }';
print '  .labqc-audit-table td[data-label="'.$langs->trans('LabQCNote').'"] { padding-bottom: 10px !important; }';
print '  .labqc-audit-table td.labqc-audit-empty { display: block; }';
print '  .labqc-audit-table td.labqc-audit-empty::before { content: none; }';
print '}';
print '</style>';
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-audit.css', 1).'" />';

print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-refresh.css', 1).'?v=1.1.0" />';
print '<div class="labqc-audit-page"><div class="div-table-responsive labqc-audit-wrap">';
print '<table class="liste centpercent labqc-audit-table">';
print '<thead><tr class="liste_titre">';
print '<th>'.$langs->trans('LabQCDate').'</th>';
print '<th>'.$langs->trans('LabQCTest').'</th>';
print '<th>'.$langs->trans('LabQCAction').'</th>';
print '<th>'.$langs->trans('LabQCObject').'</th>';
print '<th>'.$langs->trans('LabQCUser').'</th>';
print '<th>'.$langs->trans('LabQCPrevious').'</th>';
print '<th>'.$langs->trans('LabQCNew').'</th>';
print '<th>'.$langs->trans('LabQCNote').'</th>';
print '</tr></thead>';
print '<tbody>';

$sql = "SELECT a.*, t.ref AS test_ref,";
$sql .= " u.login, u.firstname, u.lastname,";
$sql .= " r.parameter AS result_parameter, r.unit AS result_unit";
$sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_audit AS a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."labqcpro_test AS t ON t.rowid=a.fk_test";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON u.rowid=a.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."labqcpro_test_result AS r";
$sql .= " ON a.object_type='test_result' AND r.rowid=a.fk_object";
$sql .= " WHERE a.entity=".(int) $conf->entity;

if ($testId > 0) {
    $sql .= " AND a.fk_test=".(int) $testId;
}

$sql .= " ORDER BY a.rowid DESC";

$resql = $db->query($sql);
$count = 0;

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $count++;

        $userLabel = trim((string) $obj->firstname.' '.(string) $obj->lastname);
        if ($userLabel === '') {
            $userLabel = (string) $obj->login;
        }

        $auditDateTimestamp = !empty($obj->datec) ? $db->jdate($obj->datec) : 0;
        $auditDateDisplay = $auditDateTimestamp ? dol_print_date($auditDateTimestamp, '%d.%m.%Y %H:%M', 'tzuser') : '—';

        print '<tr class="oddeven">';
        print '<td class="nowrap" data-label="'.$langs->trans('LabQCDate').'"><span class="labqc-audit-value">'.dol_escape_htmltag($auditDateDisplay).'</span></td>';
        print '<td data-label="'.$langs->trans('LabQCTest').'"><span class="labqc-audit-value"><a href="'.dol_buildpath('/labqcpro/tests.php', 1).'?id='.(int) $obj->fk_test.'">'.dol_escape_htmltag($obj->test_ref).'</a></span></td>';
        print '<td data-label="'.$langs->trans('LabQCAction').'"><span class="labqc-audit-value">'.labqcproAuditActionBadge($obj->action).'</span></td>';

        print '<td data-label="'.$langs->trans('LabQCObject').'"><span class="labqc-audit-value">';
        if ($obj->object_type === 'test_result' && !empty($obj->result_parameter)) {
            print dol_escape_htmltag($obj->result_parameter);
            print ' <span class="opacitymedium">(#'.(int) $obj->fk_object.')</span>';
        } else {
            print dol_escape_htmltag($obj->object_type === 'test' ? $langs->trans('LabQCTestObject') : $obj->object_type);
            if (!empty($obj->fk_object)) {
                print ' #'.(int) $obj->fk_object;
            }
        }
        print '</span></td>';

        print '<td data-label="'.$langs->trans('LabQCUser').'"><span class="labqc-audit-value">'.dol_escape_htmltag($userLabel).'</span></td>';

        $oldDisplay = labqcproFormatAuditValue(
            $obj->old_value,
            $obj->object_type,
            $obj->action,
            $obj->result_unit
        );
        $newDisplay = labqcproFormatAuditValue(
            $obj->new_value,
            $obj->object_type,
            $obj->action,
            $obj->result_unit
        );

        print '<td data-label="'.$langs->trans('LabQCPrevious').'"><span class="labqc-audit-value">'.dol_escape_htmltag($oldDisplay).'</span></td>';
        print '<td data-label="'.$langs->trans('LabQCNew').'"><span class="labqc-audit-value">'.dol_escape_htmltag($newDisplay).'</span></td>';
        print '<td class="labqc-audit-note" data-label="'.$langs->trans('LabQCNote').'"><span class="labqc-audit-value">'.dol_escape_htmltag(labqcproAuditNoteLabel($obj->note)).'</span></td>';
        print '</tr>';
    }
}

if ($count === 0) {
    print '<tr><td class="labqc-audit-empty" colspan="8"><span class="opacitymedium">'.$langs->trans('LabQCNoAuditEvents').'</span></td></tr>';
}

print '</tbody>';
print '</table>';
print '</div></div>';

llxFooter();
$db->close();
