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
 * LabQC Pro
 * Quality Control Management for Dolibarr
 * Copyright (C) 2026 PanciucSoft
 */

// Load Dolibarr environment
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

llxHeader('', $langs->trans('ModuleLabQCProName'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'" />';


print '<div class="labqc-dashboard">';
print '<div class="labqc-hero">';
print '<div><div class="labqc-eyebrow">'.dol_escape_htmltag($langs->trans('LabQCDashboardEyebrow')).'</div>';
print '<h2>LabQC Pro</h2><p>'.$langs->trans('LabQCQualityControlManagement').'</p></div>';

print '</div>';

// Read-only, entity-scoped dashboard metrics. Missing tables fail gracefully.
$qcCounts = array('specifications' => null, 'total' => null, 'pass' => null, 'fail' => null, 'pending' => null);
$entity = (int) $conf->entity;
$statsSql = array(
    'specifications' => "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."labqcpro_specification WHERE entity=".$entity,
    'total' => "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."labqcpro_test WHERE entity=".$entity,
    'pass' => "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."labqcpro_test WHERE entity=".$entity." AND status=1",
    'fail' => "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."labqcpro_test WHERE entity=".$entity." AND status=2",
    'pending' => "SELECT COUNT(*) AS n FROM ".MAIN_DB_PREFIX."labqcpro_test WHERE entity=".$entity." AND status=0",
);
foreach ($statsSql as $metric => $sql) {
    $result = $db->query($sql);
    if ($result) {
        $obj = $db->fetch_object($result);
        $qcCounts[$metric] = $obj ? (int) $obj->n : null;
        $db->free($result);
    }
}
$metrics = array(
    array('specifications', 'LabQCSpecifications', 'specifications.php', 'spec'),
    array('total', 'LabQCDashboardTotalTests', 'tests.php', 'total'),
    array('pass', 'PASS', 'tests.php', 'pass'),
    array('fail', 'FAIL / OOS', 'tests.php', 'fail'),
    array('pending', 'LabQCInProgress', 'tests.php', 'pending'),
);
print '<div class="labqc-kpis">';
foreach ($metrics as $metric) {
    $number = $qcCounts[$metric[0]] === null ? '—' : (string) $qcCounts[$metric[0]];
    print '<a class="labqc-kpi labqc-kpi-'.$metric[3].'" href="'.dol_buildpath('/labqcpro/'.$metric[2], 1).'">';
    print '<span class="labqc-kpi-label">'.dol_escape_htmltag($langs->trans($metric[1])).'</span>';
    print '<strong>'.dol_escape_htmltag($number).'</strong><span class="labqc-kpi-link">'.dol_escape_htmltag($langs->trans('LabQCDashboardViewRecords')).' &rarr;</span></a>';
}
print '</div>';
print '<div class="labqc-dashboard-grid">';
print '<section class="labqc-panel"><div class="labqc-panel-heading"><h3>'.dol_escape_htmltag($langs->trans('LabQCDashboardWorkflow')).'</h3><span>'.dol_escape_htmltag($langs->trans('LabQCDashboardLabOperations')).'</span></div>';
print '<div class="labqc-workflow">';
$steps = array(
    array('01', 'LabQCDashboardSpecificationsStep', 'LabQCDashboardSpecificationsHint', 'specifications.php'),
    array('02', 'LabQCDashboardTestingStep', 'LabQCDashboardTestingHint', 'tests.php'),
    array('03', 'LabQCDashboardReportsStep', 'LabQCDashboardReportsHint', 'coa.php'),
    array('04', 'LabQCAuditTrail', 'LabQCDashboardAuditHint', 'audit.php'),
);
foreach ($steps as $step) {
    print '<a class="labqc-step" href="'.dol_buildpath('/labqcpro/'.$step[3], 1).'"><span class="labqc-step-no">'.$step[0].'</span><span><strong>'.$langs->trans($step[1]).'</strong><small>'.$langs->trans($step[2]).'</small></span><span class="labqc-arrow">&rarr;</span></a>';
}
print '</div></section>';
print '<section class="labqc-panel"><div class="labqc-panel-heading"><h3>'.dol_escape_htmltag($langs->trans('LabQCDashboardModuleInfo')).'</h3><span>'.dol_escape_htmltag(html_entity_decode($langs->trans('LabQCDashboardIntegrationStatus'), ENT_QUOTES | ENT_HTML5, 'UTF-8')).'</span></div>';
print '<div class="labqc-info-row"><span>'.dol_escape_htmltag($langs->trans('LabQCDashboardModuleId')).'</span><strong>207603</strong></div>';
print '<div class="labqc-info-row"><span>'.dol_escape_htmltag($langs->trans('LabQCDashboardReleaseChannel')).'</span><span class="labqc-pill labqc-pill-ok">'.dol_escape_htmltag($langs->trans('LabQCStable')).'</span></div>';
print '<div class="labqc-info-row"><span>BatchSafe Pro</span>';
print isModEnabled('batchsafepro') ? '<span class="labqc-pill labqc-pill-ok">'.dol_escape_htmltag($langs->trans('LabQCDashboardEnabled')).'</span>' : '<span class="labqc-pill labqc-pill-muted">'.dol_escape_htmltag($langs->trans('LabQCDashboardNotEnabled')).'</span>';
print '</div>';
print '<div class="labqc-info-row"><span>CAPA &amp; Deviation Manager</span>';
print isModEnabled('capadeviationmanager') ? '<span class="labqc-pill labqc-pill-ok">'.dol_escape_htmltag($langs->trans('LabQCDashboardEnabled')).'</span>' : '<span class="labqc-pill labqc-pill-muted">'.dol_escape_htmltag($langs->trans('LabQCDashboardCheckConfig')).'</span>';
print '</div>';
print '<p class="labqc-notice">'.dol_escape_htmltag($langs->trans('LabQCDashboardIntegrationNotice')).'</p>';
print '</section></div></div>';

llxFooter();
$db->close();
