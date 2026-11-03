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
 * LabQC Pro - Certificates of Analysis
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
require_once __DIR__.'/lib/labqcpro-inspection.lib.php';
$langs->loadLangs(array('labqcpro@labqcpro'));

if (!labqcproUserCan($user, 'qc', 'read')) {
    accessforbidden($langs->trans('LabQCNoReadPermission'));
}

// Additive, idempotent database setup on first page use (no module reactivation).
if (!labqcproInspectionEnsureSchema($db)) {
    accessforbidden($langs->trans('LabQCInspectionSetupError'));
}

$canGenerateDocuments = labqcproUserCan($user, 'document', 'generate');

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');

function labqcproCoaStatusLabel($status)
{
    global $langs;
    if ((int) $status === 1) {
        return '<span class="badge badge-status4">PASS</span>';
    }
    if ((int) $status === 2) {
        return '<span class="badge badge-status8">FAIL</span>';
    }

    return '<span class="badge badge-status1">'.$langs->trans('LabQCInProgress').'</span>';
}

function labqcproCoaVerdictText($verdict)
{
    if ((int) $verdict === 1) {
        return 'PASS';
    }
    if ((int) $verdict === 2) {
        return 'FAIL';
    }

    return 'PENDING';
}

function labqcproCoaCriteriaText($type, $min, $max, $target, $unit)
{
    $unitText = trim((string) $unit);
    $unitText = $unitText !== '' ? ' '.$unitText : '';

    if ($type === 'range') {
        return $min.' - '.$max.$unitText;
    }
    if ($type === 'max') {
        return '<= '.$max.$unitText;
    }
    if ($type === 'min') {
        return '>= '.$min.$unitText;
    }
    if ($type === 'equal') {
        return '= '.$target.$unitText;
    }

    return (string) $target;
}

function labqcproCoaResultText($line)
{
    if ($line->criteria_type === 'range' || $line->criteria_type === 'max' || $line->criteria_type === 'min') {
        if ($line->result_value === null || $line->result_value === '') {
            return '';
        }

        $unitText = trim((string) $line->unit);
        return $line->result_value.($unitText !== '' ? ' '.$unitText : '');
    }

    return (string) $line->result_text;
}


function labqcproCoaNormalizePosition($position)
{
    $position = trim((string) $position);

    // Older approvals may contain a visual dash placeholder instead of an empty value.
    if ($position === '' || in_array($position, array('-', '--', '—', '–', '_'), true)) {
        return '';
    }

    return $position;
}


function labqcproCoaGetApproval($db, $testId, $entity)
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
 * Resolve the laboratory user who most recently entered or modified analytical results.
 * Falls back to the test creator for legacy tests without result_updated audit events.
 *
 * @param DoliDB $db Database handler
 * @param int $testId QC test ID
 * @param int $entity Entity
 * @return string Display name
 */
function labqcproCoaGetTesterName($db, $testId, $entity)
{
    $actions = array('result_updated', 'test_created');

    foreach ($actions as $action) {
        $sql = "SELECT u.firstname, u.lastname, u.login";
        $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_audit AS a";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON u.rowid=a.fk_user";
        $sql .= " WHERE a.fk_test=".(int) $testId;
        $sql .= " AND a.entity=".(int) $entity;
        $sql .= " AND a.action='".$db->escape($action)."'";
        $sql .= " ORDER BY a.rowid DESC LIMIT 1";

        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            $name = trim((string) $obj->firstname.' '.(string) $obj->lastname);
            if ($name === '') {
                $name = trim((string) $obj->login);
            }
            if ($name !== '') {
                return $name;
            }
        }
    }

    global $langs;
    return $langs->trans('LabQCNotRecorded');
}

/**
 * Format a Unix timestamp in the fixed European format used by LabQC final PDFs.
 * User timezone conversion is still delegated to Dolibarr.
 *
 * @param int $timestamp Unix timestamp
 * @param bool $withTime Include 24-hour time
 * @return string
 */
function labqcproCoaFormatTimestamp($timestamp, $withTime = false)
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    return dol_print_date(
        $timestamp,
        $withTime ? '%d.%m.%Y %H:%M' : '%d.%m.%Y',
        'tzuser'
    );
}

/**
 * Format a SQL date/datetime in fixed European day.month.year format.
 *
 * @param DoliDB $db Database handler
 * @param string $value SQL date/datetime
 * @param bool $withTime Include 24-hour time
 * @return string
 */
function labqcproCoaFormatDate($db, $value, $withTime = false)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $value .= ' 00:00:00';
    }

    $timestamp = $db->jdate($value);
    if (empty($timestamp)) {
        return trim((string) $value);
    }

    return labqcproCoaFormatTimestamp($timestamp, $withTime);
}

/*
 * Generate PDF
 */

if ($action === 'pdf' && $id > 0) {
    if (!$canGenerateDocuments) {
        accessforbidden($langs->trans('LabQCNoDocumentPermission'));
    }
    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

    $sql = "SELECT t.rowid, t.ref, t.batch_number, t.date_test, t.status, t.notes,";
    $sql .= " p.ref AS product_ref, p.label AS product_label,";
    $sql .= " s.ref AS spec_ref, s.label AS spec_label, s.version AS spec_version";
    $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_test AS t";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=t.fk_product";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."labqcpro_specification AS s ON s.rowid=t.fk_specification";
    $sql .= " WHERE t.rowid=".(int) $id;
    $sql .= " AND t.entity=".(int) $conf->entity;

    $resql = $db->query($sql);
    $test = ($resql && $db->num_rows($resql)) ? $db->fetch_object($resql) : null;

    if (!$test) {
        accessforbidden($langs->trans('LabQCTestNotFound'));
    }
    $inspection = labqcproInspectionRead($db, (int) $conf->entity, (int) $test->rowid);

    if ((int) $test->status === 0) {
        accessforbidden($langs->trans('LabQCCertificateStillInProgress'));
    }

    $approval = labqcproCoaGetApproval($db, (int) $test->rowid, (int) $conf->entity);
    if (!$approval) {
        accessforbidden($langs->trans('LabQCFinalizeBeforeDocument'));
    }

    $results = array();

    $sql = "SELECT rowid, parameter, method, criteria_type, min_value, max_value, target_text, unit, mandatory,";
    $sql .= " result_value, result_text, verdict, comment";
    $sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_test_result";
    $sql .= " WHERE fk_test=".(int) $test->rowid;
    $sql .= " ORDER BY position ASC, rowid ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $results[] = $obj;
        }
    }

    $format = pdf_getFormat();
    $pdf = pdf_getInstance(array($format['width'], $format['height']));

    if (method_exists($pdf, 'setPrintHeader')) {
        $pdf->setPrintHeader(false);
    }
    if (method_exists($pdf, 'setPrintFooter')) {
        $pdf->setPrintFooter(false);
    }

    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $finalStatus = ((int) $test->status === 1) ? 'PASS' : 'FAIL';
    $isPass = ((int) $test->status === 1);
    $overallResult = $isPass ? $langs->trans('LabQCCompliesSpecification') : $langs->trans('LabQCOutOfSpecification');
    $finalDecision = $isPass ? $langs->trans('LabQCApproved') : $langs->trans('LabQCRejected');

    $pdf->SetCreator('Dolibarr '.DOL_VERSION);
    $pdf->SetAuthor($mysoc->name);
    $pdf->SetTitle($isPass ? $langs->trans('LabQCPDFTitleCoA', $test->ref) : $langs->trans('LabQCPDFTitleOOS', $test->ref));
    $pdf->SetSubject($isPass ? $langs->trans('LabQCPDFSubjectCoA') : $langs->trans('LabQCPDFSubjectOOS'));
    $pdf->SetFont(pdf_getPDFFont($langs), '', 10);
    $pdf->AddPage();

    $documentTitle = $isPass ? $langs->trans('LabQCCertificateOfAnalysis') : $langs->trans('LabQCOOSQCTestReport');
    // The OOS title is longer in some languages (notably Romanian), so use a slightly smaller
    // title size to keep it on one line without changing the CoA appearance.
    $documentTitleFontSize = $isPass ? 16 : 13;
    $documentRefLabel = $isPass ? $langs->trans('LabQCCoARef') : $langs->trans('LabQCOOSRef');

    $testDate = !empty($test->date_test) ? labqcproCoaFormatDate($db, $test->date_test, false) : '';

    $companyName = dol_escape_htmltag($mysoc->name);
    $companyAddress = dol_escape_htmltag(trim($mysoc->address));
    $companyZipTown = dol_escape_htmltag(trim($mysoc->zip.' '.$mysoc->town));

    // Controlled, print-friendly report layout. Values remain sourced from the finalized QC record.
    $ink = '#22374B';
    $blue = '#244F6B';
    $pale = '#EAF2F7';
    $muted = '#566878';
    $decisionColor = $isPass ? '#166447' : '#983C25';
    $decisionFill = $isPass ? '#E6F5ED' : '#FCECE7';
    $escape = function ($value) { return dol_escape_htmltag((string) $value); };
    $approvalPosition = labqcproCoaNormalizePosition($approval->approver_position);
    $approvalDateDisplay = labqcproCoaFormatDate($db, $approval->approval_date, true);
    $testerName = labqcproCoaGetTesterName($db, (int) $test->rowid, (int) $conf->entity);
    $generatedDate = labqcproCoaFormatTimestamp(dol_now(), true);
    $approvedName = trim((string) $approval->approver_name);
    $html = '';
    $html .= '<table width="100%" cellpadding="8" cellspacing="0" border="0"><tr>';
    $html .= '<td width="55%" style="background-color:'.$blue.';color:#FFFFFF"><span style="font-size:15px;font-weight:bold;color:#FFFFFF">'.$companyName.'</span>';
    if ($companyAddress !== '') $html .= '<br><span style="font-size:9px;color:#FFFFFF">'.$companyAddress.'</span>';
    if ($companyZipTown !== '') $html .= '<br><span style="font-size:9px;color:#FFFFFF">'.$companyZipTown.'</span>';
    $html .= '</td><td width="45%" align="right" style="background-color:'.$blue.';color:#FFFFFF">';
    $html .= '<strong style="font-size:'.($isPass ? '13' : '12').'px;color:#FFFFFF">'.$escape($documentTitle).'</strong>';
    $html .= '<br><span style="font-size:9px;color:#FFFFFF">'.$escape($documentRefLabel).': '.$escape($test->ref).'</span></td></tr></table>';
    $html .= '<table width="100%" cellpadding="7" border="0"><tr><td style="font-size:10px;color:'.$muted.'">'.$escape($langs->trans('LabQCPDFLabControl')).' &nbsp; | &nbsp; '.$escape($generatedDate).'</td></tr></table>';
    $html .= '<br><table cellpadding="5" cellspacing="0" width="100%" border="0">';
    $html .= '<tr><td colspan="2" style="background-color:'.$pale.';color:'.$blue.'"><strong>'.$escape($langs->trans('LabQCPDFTestIdentification')).'</strong></td></tr>';
    $info = array(
        array($langs->trans('LabQCProduct'), $test->product_ref.' - '.$test->product_label),
        array($langs->trans('LabQCBatchLot'), $test->batch_number),
        array($langs->trans('LabQCSpecification'), $test->spec_ref.' - '.$test->spec_label.' v'.$test->spec_version),
        array($langs->trans('LabQCTestDate'), $testDate),
        array($langs->trans('LabQCFinalQCStatus'), $finalStatus),
        array($langs->trans('LabQCOverallResult'), $overallResult),
    );
    foreach ($info as $i => $item) {
        $bg = $i % 2 === 0 ? '#F6F9FB' : '#FFFFFF';
        $html .= '<tr style="background-color:'.$bg.'"><td width="30%" style="color:'.$muted.'"><strong>'.$escape($item[0]).'</strong></td><td width="70%">'.$escape($item[1]).'</td></tr>';
    }
    $html .= '</table><br>';
    // Optional inspection metadata. Hide the section for legacy records without values.
    if (count(array_filter($inspection, 'strlen')) > 0) {
        $pairs = array(
            array('LabQCBatchQuantity', 'batch_quantity', 'LabQCQuantityInspected', 'quantity_inspected'),
            array('LabQCAqlLevel', 'aql_level', 'LabQCInspectionType', 'inspection_type')
        );
        $html .= '<table width="100%" cellpadding="4" border="0">';
        $html .= '<tr><td colspan="4" style="background-color:'.$pale.';color:'.$blue.'"><strong>'.$escape($langs->trans('LabQCInspectionDetails')).'</strong></td></tr>';
        foreach ($pairs as $pair) {
            $left = $inspection[$pair[1]] !== '' ? $inspection[$pair[1]] : '—';
            $right = $pair[3] === 'inspection_type' ? labqcproInspectionTypeLabel($inspection[$pair[3]], $langs) : $inspection[$pair[3]];
            if ($right === '') $right = '—';
            $html .= '<tr style="background-color:#F6F9FB"><td width="27%"><strong>'.$escape($langs->trans($pair[0])).'</strong></td><td width="23%">'.$escape($left).'</td>';
            $html .= '<td width="27%"><strong>'.$escape($langs->trans($pair[2])).'</strong></td><td width="23%">'.$escape($right).'</td></tr>';
        }
        $thirdRef = $inspection['third_party_reference'] !== '' ? $inspection['third_party_reference'] : '—';
        $html .= '<tr style="background-color:#F6F9FB"><td width="27%"><strong>'.$escape($langs->trans('LabQCThirdPartyReference')).'</strong></td>';
        $html .= '<td width="73%" colspan="3">'.$escape($thirdRef).'</td></tr></table>';
    }
    $html .= '<table cellpadding="5" cellspacing="0" width="100%" border="0">';
    $html .= '<tr><td colspan="5" style="background-color:'.$pale.';color:'.$blue.'"><strong>'.$escape($langs->trans('LabQCPDFAnalyticalResults')).'</strong></td></tr>';
    $html .= '<tr style="background-color:#DDEAF2;color:'.$ink.';font-weight:bold">';
    foreach (array(array('LabQCParameter',22),array('LabQCMethod',16),array('LabQCSpecification',24),array('LabQCResult',22),array('LabQCVerdict',16)) as $col) {
        $html .= '<td width="'.$col[1].'%"><strong>'.$escape($langs->trans($col[0])).'</strong></td>';
    }
    $html .= '</tr>';
    foreach ($results as $i => $line) {
        $criteria = labqcproCoaCriteriaText($line->criteria_type,$line->min_value,$line->max_value,$line->target_text,$line->unit);
        $resultText = labqcproCoaResultText($line);
        $verdict = labqcproCoaVerdictText($line->verdict);
        $bg = $i % 2 ? '#FFFFFF' : '#F6F9FB';
        $vcolor = $verdict === 'PASS' ? '#166447' : ($verdict === 'FAIL' ? '#983C25' : $muted);
        $html .= '<tr style="background-color:'.$bg.'">';
        foreach (array(array($line->parameter,22),array($line->method,16),array($criteria,24),array($resultText,22)) as $cell) {
            $html .= '<td width="'.$cell[1].'%">'.$escape($cell[0]).'</td>';
        }
        $html .= '<td width="16%" style="color:'.$vcolor.'"><strong>'.$escape($verdict).'</strong></td></tr>';
    }
    $html .= '</table>';
    if (!empty($test->notes)) {
        $html .= '<br><table cellpadding="6" width="100%" border="0"><tr><td style="background-color:#F6F9FB"><strong>'.$escape($langs->trans('LabQCNotes')).'</strong><br>'.nl2br($escape($test->notes)).'</td></tr></table>';
    }
    $html .= '<br><table width="100%" cellpadding="7" border="0"><tr><td style="background-color:'.$decisionFill.';color:'.$decisionColor.'">';
    $html .= '<strong>'.$escape($langs->trans('LabQCFinalDecision')).': '.$escape($finalDecision).'</strong>';
    $html .= '<br>'.$escape($overallResult);
    if (!$isPass) {
        $html .= '<br><span style="font-size:9px">'.$escape($langs->trans('LabQCOOSExplanation')).'</span>';
    }
    $html .= '</td></tr></table><br>';
    $html .= '<table width="100%" cellpadding="5" border="0">';
    $html .= '<tr><td colspan="2" style="background-color:'.$pale.';color:'.$blue.'"><strong>'.str_replace('&amp;', '&', $escape($langs->trans('LabQCPDFTraceabilityApproval'))).'</strong></td></tr>';
    $trace = array(
        array($langs->trans('LabQCTestedBy'), $testerName),
        array($langs->trans('LabQCApprovalDate'), $approvalDateDisplay),
        array($langs->trans('LabQCGenerated'), $generatedDate),
        array($langs->trans('LabQCNameSurname'), $approvedName !== '' ? $approvedName : $langs->trans('LabQCNotRecorded')),
        array($langs->trans('LabQCPosition'), $approvalPosition !== '' ? $approvalPosition : $langs->trans('LabQCNotRecorded')),
    );
    foreach ($trace as $i => $entry) {
        $html .= '<tr style="background-color:'.($i%2 ? '#FFFFFF':'#F6F9FB').'"><td width="36%"><strong>'.$escape($entry[0]).'</strong></td><td width="64%">'.$escape($entry[1]).'</td></tr>';
    }
    $html .= '</table>';
    $html .= '<p style="font-size:9px;color:'.$muted.'">'.$escape($langs->trans('LabQCPDFApprovalDisclaimer')).'</p>';
    // Reference already appears in the document header and every page footer.
    // Do not add a final body table: short CoA/OOS reports could move that table to a nearly blank page.

    // Avoid a trailing line advance that can spill onto an otherwise blank page.
    $pdf->writeHTML($html, false, false, true, false, '');

    // Place footers on existing pages only, never changing pagination while drawing them.
    $pageCount = $pdf->getNumPages();
    $pdf->SetAutoPageBreak(false, 0);
    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $pdf->setPage($pageNumber);
        $footerY = $pdf->getPageHeight() - 16;
        $pdf->SetDrawColor(200, 214, 225);
        $pdf->Line(15, $footerY, $pdf->getPageWidth() - 15, $footerY);
        $pdf->SetFont(pdf_getPDFFont($langs), '', 8);
        $pdf->SetTextColor(83, 103, 119);
        // Absolute-positioned text avoids a footer Cell() changing the active page or
        // causing a page break (observed with PDFs containing inspection metadata).
        // TCPDF Text() expects plain Unicode text. Dolibarr trans() can provide
        // HTML entities (e.g. P&amp;aacute;gina); never render those literally.
        // transnoentitiesnoconv() is preferred where available. Decode up to
        // two remaining layers to handle hosts returning doubly escaped text.
        $pageLabel = method_exists($langs, 'transnoentitiesnoconv')
            ? $langs->transnoentitiesnoconv('LabQCPDFPage')
            : $langs->trans('LabQCPDFPage');
        $pageLabel = html_entity_decode(html_entity_decode((string) $pageLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $footerText = 'LabQC Pro  |  '.(string) $test->ref.'  |  '.$pageLabel.' '.$pageNumber.' / '.$pageCount;
        $footerX = max(15, $pdf->getPageWidth() - 15 - $pdf->GetStringWidth($footerText));
        $pdf->Text($footerX, $footerY + 4, $footerText);
    }

    $filenamePrefix = $isPass ? 'CoA_' : 'OOS_Report_';
    $filename = $filenamePrefix.dol_sanitizeFileName($test->product_ref).'_'.dol_sanitizeFileName($test->batch_number).'_'.$finalStatus.'.pdf';

    if (ob_get_length()) {
        @ob_end_clean();
    }

    $pdf->Output($filename, 'I');
    exit;
}

/*
 * List page
 */

llxHeader('', $langs->trans('LabQCCertificatesReports'));
print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-ui.css', 1).'" />';

print labqcproPageHero($langs->trans('LabQCCertificatesReports'), $langs->trans('LabQCCertificatesSubtitle'));

print '<style>';
print '@media (max-width: 767px) {';
print '  .labqc-doc-wrap { overflow: visible !important; }';
print '  table.labqc-doc-table { display: block; width: 100%; border-collapse: separate; }';
print '  .labqc-doc-table thead { display: none; }';
print '  .labqc-doc-table tbody { display: flex !important; flex-direction: column; width: 100%; gap: 12px; }';
print '  .labqc-doc-table tr.oddeven { display: flex !important; flex-direction: column; width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; margin: 0; padding: 6px 0; background: #fff; overflow: hidden; }';
print '  .labqc-doc-table td { display: flex !important; align-items: flex-start; width: 100% !important; height: auto !important; min-height: 0 !important; box-sizing: border-box; padding: 6px 10px !important; border: 0 !important; white-space: normal !important; overflow: visible !important; line-height: 1.4; text-align: left !important; }';
print '  .labqc-doc-table td::before { content: attr(data-label); flex: 0 0 100px; width: 100px; min-width: 100px; padding-right: 8px; box-sizing: border-box; font-weight: 600; color: inherit; }';
print '  .labqc-doc-value { display: block; flex: 1 1 auto; min-width: 0; max-width: 100%; height: auto !important; white-space: normal !important; overflow-wrap: anywhere !important; word-break: break-word !important; hyphens: auto; line-height: 1.4; }';
print '  .labqc-doc-table td[data-label="'.$langs->trans('LabQCProduct').'"] { padding-bottom: 8px !important; }';
print '  .labqc-doc-table td[data-label="'.$langs->trans('LabQCCertificate').'"] { padding-top: 9px !important; padding-bottom: 12px !important; }';
print '  .labqc-doc-table td[data-label="'.$langs->trans('LabQCCertificate').'"] .butAction { display: inline-block; margin: 0; max-width: 100%; white-space: normal !important; text-align: center; line-height: 1.25; }';
print '  .labqc-doc-table td.labqc-doc-empty { display: block !important; }';
print '  .labqc-doc-table td.labqc-doc-empty::before { content: none; }';
print '}';
print '</style>';

print '<link rel="stylesheet" href="'.dol_buildpath('/labqcpro/css/labqcpro-refresh.css', 1).'?v=1.1.0" />';
print '<div class="labqc-doc-page">';
print '<div class="labqc-doc-heading"><div><strong>'.$langs->trans('LabQCCertificatesReports').'</strong><span>'.$langs->trans('LabQCCertificatesSubtitle').'</span></div></div>';
print '<div class="div-table-responsive labqc-doc-wrap">';
print '<table class="liste centpercent labqc-doc-table">';
print '<thead><tr class="liste_titre">';
print '<th>'.$langs->trans('LabQCTest').'</th>';
print '<th>'.$langs->trans('LabQCProduct').'</th>';
print '<th>'.$langs->trans('LabQCBatchLot').'</th>';
print '<th>'.$langs->trans('LabQCTestDate').'</th>';
print '<th>'.$langs->trans('LabQCStatus').'</th>';
print '<th class="right">'.$langs->trans('LabQCCertificate').'</th>';
print '</tr></thead>';
print '<tbody>';

$sql = "SELECT t.rowid, t.ref, t.batch_number, t.date_test, t.status,";
$sql .= " p.ref AS product_ref, p.label AS product_label,";
$sql .= " a.rowid AS approval_id, a.approver_name, a.approval_date";
$sql .= " FROM ".MAIN_DB_PREFIX."labqcpro_test AS t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid=t.fk_product";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."labqcpro_test_approval AS a";
$sql .= " ON a.fk_test=t.rowid AND a.entity=t.entity";
$sql .= " WHERE t.entity=".(int) $conf->entity;
$sql .= " ORDER BY t.rowid DESC";

$resql = $db->query($sql);
$count = 0;

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $count++;

        print '<tr class="oddeven">';
        print '<td data-label="'.$langs->trans('LabQCTest').'"><span class="labqc-doc-value">'.dol_escape_htmltag($obj->ref).'</span></td>';
        print '<td class="labqc-doc-product" data-label="'.$langs->trans('LabQCProduct').'"><span class="labqc-doc-value">'.dol_escape_htmltag($obj->product_ref.' - '.$obj->product_label).'</span></td>';
        print '<td data-label="'.$langs->trans('LabQCBatchLot').'"><span class="labqc-doc-value">'.dol_escape_htmltag($obj->batch_number).'</span></td>';
        print '<td data-label="'.$langs->trans('LabQCTestDate').'"><span class="labqc-doc-value">'.dol_escape_htmltag(labqcproCoaFormatDate($db, $obj->date_test, false)).'</span></td>';
        print '<td data-label="'.$langs->trans('LabQCStatus').'"><span class="labqc-doc-value">'.labqcproCoaStatusLabel($obj->status).'</span></td>';
        print '<td class="right labqc-doc-certificate" data-label="'.$langs->trans('LabQCCertificate').'"><span class="labqc-doc-value">';

        if ((int) $obj->status === 0) {
            print '<span class="opacitymedium">'.$langs->trans('LabQCCompleteTestFirst').'</span>';
        } elseif (empty($obj->approval_id)) {
            print '<span class="opacitymedium">'.$langs->trans('LabQCFinalizeTestFirst').'</span>';
        } else {
            if ($canGenerateDocuments) {
                $buttonLabel = ((int) $obj->status === 1) ? $langs->trans('LabQCGenerateCoAPDF') : $langs->trans('LabQCGenerateOOSPDF');
                print '<a class="butAction" target="_blank" href="'.dol_buildpath('/labqcpro/coa.php', 1).'?action=pdf&amp;id='.(int) $obj->rowid.'&amp;token='.urlencode(newToken()).'">'.$buttonLabel.'</a>';
            } else {
                print '<span class="opacitymedium">'.$langs->trans('LabQCNoDocumentPermissionShort').'</span>';
            }
        }

        print '</span></td>';
        print '</tr>';
    }
}

if ($count === 0) {
    print '<tr><td class="labqc-doc-empty" colspan="6"><span class="opacitymedium">'.$langs->trans('LabQCNoTestsAvailable').'</span></td></tr>';
}

print '</tbody>';
print '</table>';
print '</div>';
print '</div>'; // labqc-doc-page

llxFooter();
$db->close();
