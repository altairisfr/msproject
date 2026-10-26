<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2025  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2026		LEVEL IT				 <info@levelit.com.ar>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mrpgantt/mrpganttindex.php
 * \ingroup mrpgantt
 * \brief   Gantt view for Manufacturing Orders
 */

// Load Dolibarr environment
$res = 0;
$paths = array(
    dirname(dirname(dirname(__FILE__)))."/main.inc.php",
    dirname(dirname(dirname(dirname(__FILE__))))."/main.inc.php",
    "../main.inc.php",
    "../../main.inc.php",
);
foreach ($paths as $path) {
    if (!$res && file_exists($path)) {
        $res = @include $path;
    }
}
if (!$res) {
    die("Include of main fails");
}

global $conf, $langs, $db, $user;

// Permissions
if (!isModEnabled('mrp')) {
	accessforbidden('Module MRP not enabled');
}
if (!$user->hasRight('mrp', 'read')) {
	accessforbidden();
}

$langs->loadLangs(array('mrp', 'mrpgantt@mrpgantt'));

$has_workstation = isModEnabled('workstation');

$scale_printed = '';

// --- Parameters ---
$scale = GETPOST('scale', 'alpha');
if (in_array($scale, array('week', 'month', 'day'))) {
	if (GETPOST('set_default', 'alpha') === '1') {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		dol_set_user_param($db, $conf, $user, array('GANTTDEFAULTSCALE_mrpgantt' => $scale));
	}
	
	$scale_printed = $user->conf->GANTTDEFAULTSCALE_mrpgantt;

	if ($scale_printed === 'week') {
		$scale_printed = $langs->trans('Week');
	} else if ($scale_printed === 'month') {
		$scale_printed = $langs->trans('Month');
	} else {
		$scale_printed = $langs->trans('Day');
	}
} else {
	$scale = (isset($user->conf->GANTTDEFAULTSCALE_mrpgantt) && in_array($user->conf->GANTTDEFAULTSCALE_mrpgantt, array('week', 'month', 'day')))
		? $user->conf->GANTTDEFAULTSCALE_mrpgantt
		: 'month';

	if ($scale === 'week') {
		$scale_printed = $langs->trans('Week');
	} elseif ($scale === 'day') {
		$scale_printed = $langs->trans('Day');
	} else {
		$scale_printed = $langs->trans('Month');
	}
}

$now = dol_now();

// Period navigation
$p_year  = GETPOSTINT('year');
$p_month = GETPOSTINT('month');
$p_week  = GETPOSTINT('week');
$p_day   = GETPOSTINT('day');

if (!$p_year)  $p_year  = (int) date('Y', $now);
if (!$p_month) $p_month = (int) date('n', $now);
if (!$p_week)  $p_week  = (int) date('W', $now);
if (!$p_day)   $p_day   = (int) date('j', $now);

// Clamp values
$p_year  = max(2000, min(2100, $p_year));
$p_month = max(1, min(12, $p_month));
$p_week  = max(1, min(53, $p_week));
$p_day   = max(1, min(31, $p_day));

// Period boundaries
if ($scale === 'month') {
	$period_start_ts = mktime(0, 0, 0, $p_month, 1, $p_year);
	$days_in_period  = (int) date('t', $period_start_ts);
	$period_end_ts   = $period_start_ts + $days_in_period * 86400;
} elseif ($scale === 'day') {
	$period_start_ts = mktime(0, 0, 0, $p_month, $p_day, $p_year);
	$days_in_period  = 1;
	$period_end_ts   = $period_start_ts + 86400;
} else {
	// ISO week: find Monday of that week
	$jan4      = mktime(0, 0, 0, 1, 4, $p_year); // Jan 4 is always in week 1
	$jan4_dow  = (int) date('N', $jan4);           // 1=Mon
	$week1_mon = $jan4 - ($jan4_dow - 1) * 86400;
	$period_start_ts = $week1_mon + ($p_week - 1) * 7 * 86400;
	$days_in_period  = 7;
	$period_end_ts   = $period_start_ts + 7 * 86400;
}

// --- SQL Query ---
$sql  = "SELECT m.rowid, m.ref, m.status, m.qty,";
$sql .= " m.date_start_planned, m.date_end_planned, m.date_creation,";
$sql .= " COALESCE(p.ref, '') as product_ref,";
$sql .= " COALESCE(p.label, p.ref, '') as product_label,";
$sql .= ($has_workstation ? " COALESCE(ws_info.machine_ref, '') as machine_ref," : " '' as machine_ref,");
$sql .= " COALESCE(prod_sums.qty_toproduce, 0) as qty_toproduce,";
$sql .= " COALESCE(prod_sums.qty_produced, 0) as qty_produced";
$sql .= " FROM ".$db->prefix()."mrp_mo as m";
$sql .= " LEFT JOIN ".$db->prefix()."product as p ON p.rowid = m.fk_product";
if ($has_workstation) {
	$sql .= " LEFT JOIN (";
	$sql .= "   SELECT mp.fk_mo, MAX(ww.ref) as machine_ref";
	$sql .= "   FROM ".$db->prefix()."mrp_production as mp";
	$sql .= "   INNER JOIN ".$db->prefix()."workstation_workstation as ww ON ww.rowid = mp.fk_default_workstation";
	$sql .= "   WHERE mp.fk_default_workstation > 0";
	$sql .= "   GROUP BY mp.fk_mo";
	$sql .= " ) as ws_info ON ws_info.fk_mo = m.rowid";
}
$sql .= " LEFT JOIN (";
$sql .= "   SELECT fk_mo,";
$sql .= "     SUM(CASE WHEN role = 'toproduce' THEN qty ELSE 0 END) as qty_toproduce,";
$sql .= "     SUM(CASE WHEN role = 'produced'  THEN qty ELSE 0 END) as qty_produced";
$sql .= "   FROM ".$db->prefix()."mrp_production";
$sql .= "   GROUP BY fk_mo";
$sql .= " ) as prod_sums ON prod_sums.fk_mo = m.rowid";
$sql .= " WHERE m.status IN (0, 1, 2, 3, 9)";
$sql .= " AND m.entity IN (".getEntity('mo').")";
$sql .= " ORDER BY m.date_start_planned ASC, m.ref ASC";

$resql = $db->query($sql);

$orders        = array();
$machines_set  = array();

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$ts_start    = $obj->date_start_planned ? $db->jdate($obj->date_start_planned) : null;
		$ts_end      = $obj->date_end_planned   ? $db->jdate($obj->date_end_planned)   : null;
		$ts_creation = $obj->date_creation      ? $db->jdate($obj->date_creation)      : null;

		$qty_toproduce = (float) $obj->qty_toproduce;
		$qty_produced  = (float) $obj->qty_produced;

		// Use mo.qty as fallback for qty_toproduce if no production lines
		if ($qty_toproduce <= 0) {
			$qty_toproduce = (float) $obj->qty;
		}

		$progress = ($qty_toproduce > 0) ? min(100, round($qty_produced / $qty_toproduce * 100)) : 0;
		$is_overdue = ($ts_end && $ts_end < $now && $progress < 100);

		$orders[] = array(
			'id'            => (int) $obj->rowid,
			'ref'           => $obj->ref,
			'status'        => (int) $obj->status,
			'product_ref'   => $obj->product_ref,
			'product_label' => $obj->product_label,
			'ts_start'      => $ts_start,
			'ts_end'        => $ts_end,
			'ts_creation'   => $ts_creation,
			'qty'           => (float) $obj->qty,
			'progress'      => $progress,
			'is_overdue'    => (bool) $is_overdue,
			'machine'       => $obj->machine_ref,
		);

		if (!empty($obj->machine_ref)) {
			$machines_set[$obj->machine_ref] = true;
		}
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

ksort($machines_set);
$machines_list = array_keys($machines_set);

// --- Compute prev/next navigation URLs ---
$base_url = dol_buildpath('/mrpgantt/mrpganttindex.php', 1);

$url_today_day   = $base_url.'?scale=day&year='.date('Y', $now).'&month='.date('n', $now).'&day='.date('j', $now);

if ($scale === 'month') {
	$prev_m = $p_month - 1; $prev_y = $p_year;
	if ($prev_m < 1)  { $prev_m = 12; $prev_y--; }
	$next_m = $p_month + 1; $next_y = $p_year;
	if ($next_m > 12) { $next_m = 1;  $next_y++; }
	$url_prev        = $base_url.'?scale=month&year='.$prev_y.'&month='.$prev_m;
	$url_next        = $base_url.'?scale=month&year='.$next_y.'&month='.$next_m;
	$url_today       = $base_url.'?scale=month&year='.date('Y', $now).'&month='.date('n', $now);
	$url_scale_today = $url_today;
	$url_scale_day   = $url_today_day;
	$url_scale_week  = $base_url.'?scale=week&year='.date('Y', $now).'&week='.date('W', $now);
	$url_scale_month = '';
} elseif ($scale === 'day') {
	$prev_ts = $period_start_ts - 86400;
	$next_ts = $period_start_ts + 86400;
	$url_prev        = $base_url.'?scale=day&year='.date('Y', $prev_ts).'&month='.date('n', $prev_ts).'&day='.date('j', $prev_ts);
	$url_next        = $base_url.'?scale=day&year='.date('Y', $next_ts).'&month='.date('n', $next_ts).'&day='.date('j', $next_ts);
	$url_today       = $url_today_day;
	$url_scale_today = $url_today_day;
	$url_scale_day   = '';
	$url_scale_week  = $base_url.'?scale=week&year='.$p_year.'&week='.date('W', $period_start_ts);
	$url_scale_month = $base_url.'?scale=month&year='.$p_year.'&month='.date('n', $period_start_ts);
} else { // week
	$prev_ts = $period_start_ts - 7 * 86400;
	$next_ts = $period_start_ts + 7 * 86400;
	$url_prev        = $base_url.'?scale=week&year='.date('Y', $prev_ts).'&week='.date('W', $prev_ts);
	$url_next        = $base_url.'?scale=week&year='.date('Y', $next_ts).'&week='.date('W', $next_ts);
	$url_today       = $base_url.'?scale=week&year='.date('Y', $now).'&week='.date('W', $now);
	$url_scale_today = $url_today;
	$url_scale_day   = $url_today_day;
	$url_scale_week  = '';
	$url_scale_month = $base_url.'?scale=month&year='.$p_year.'&month='.date('n', $period_start_ts);
}

// Period label
if ($scale === 'month') {
	$period_label = dol_print_date($period_start_ts, '%B %Y');
} elseif ($scale === 'day') {
	$period_label = dol_print_date($period_start_ts, 'daytext');
} else {
	$week_end_ts  = $period_start_ts + 6 * 86400;
	$period_label = $langs->trans('Week').' '.$p_week.' &mdash; '.dol_print_date($period_start_ts, 'day').' '.$langs->trans('To').' '.dol_print_date($week_end_ts, 'day');
}

// --- Encode data for JavaScript ---
$js_orders = array();
foreach ($orders as $o) {
	$js_orders[] = array(
		'id'            => $o['id'],
		'ref'           => $o['ref'],
		'status'        => $o['status'],
		'product_ref'   => $o['product_ref'],
		'product_label' => $o['product_label'],
		'ts_start'       => $o['ts_start'],
		'ts_end'         => $o['ts_end'],
		'ts_creation'    => $o['ts_creation'],
		'date_start_str' => $o['ts_start'] ? dol_print_date($o['ts_start'], 'dayhour') : '',
		'date_end_str'   => $o['ts_end']   ? dol_print_date($o['ts_end'], 'dayhour')   : '',
		'progress'      => $o['progress'],
		'is_overdue'    => $o['is_overdue'],
		'machine'       => $o['machine'],
		'qty'           => $o['qty'],
	);
}

$mo_card_url = dol_buildpath('/mrp/mo_card.php', 1);

// --- Page output ---
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form = new Form($db);

llxHeader('', $langs->trans('GanttMO'), '', '', 0, 0, '', '', '', 'mod-mrp page-gantt');

print load_fiche_titre($langs->trans('GanttMO'), '', 'mrp');

// Responsive warning for small screens
print '<div class="gantt-small-screen-warning">';
print '<span class="fa fa-warning"></span> '.$langs->trans('GanttMinResolution');
print '</div>';

// ---- TOOLBAR ----
print '<div class="gantt-toolbar">';
print '<div class="gantt-toolbar-row">';

// Scale buttons + default view selector
print '<div class="gantt-scale-btns">';
if ($url_scale_week) {
	print '<a href="'.dol_escape_htmltag($url_scale_week).'" class="button smallpadding">'.$langs->trans('Week').'</a>';
} else {
	print '<span class="button smallpadding gantt-scale-active">'.$langs->trans('Week').'</span>';
}
if ($url_scale_month) {
	print '<a href="'.dol_escape_htmltag($url_scale_month).'" class="button smallpadding">'.$langs->trans('Month').'</a>';
} else {
	print '<span class="button smallpadding gantt-scale-active">'.$langs->trans('Month').'</span>';
}
if ($url_scale_day) {
	print '<a href="'.dol_escape_htmltag($url_scale_day).'" class="button smallpadding">'.$langs->trans('Day').'</a>';
} else {
	print '<span class="button smallpadding gantt-scale-active">'.$langs->trans('Day').'</span>';
}
print '</div>';


// Navigation
$gantt_goto_ts = dol_mktime(0, 0, 0, $p_month, $p_day, $p_year);
print '<div class="gantt-nav">';
print '<a href="'.dol_escape_htmltag($url_prev).'" class="button smallpadding" title="'.$langs->trans('Previous').'">&#8249; '.$langs->trans('Previous').'</a>';
print '<a href="'.dol_escape_htmltag($url_scale_today).'" class="button smallpadding">'.$langs->trans('Today').'</a>';
print '<span class="gantt-goto-date-wrap">';
print $form->selectDate($gantt_goto_ts, 'ganttgoto', 0, 0, 0, '', 1, 0);
print '<button type="button" id="gantt-goto-btn" class="button smallpadding"><span class="fa fa-search"></span></button>';
print '</span>';
print '<a href="'.dol_escape_htmltag($url_next).'" class="button smallpadding" title="'.$langs->trans('Next').'">'.$langs->trans('Next').' &#8250;</a>';
print '</div>';

// Period label
print '<strong class="gantt-period-label">'.dol_escape_htmltag($period_label).'</strong>';
print '</div>'; // end toolbar row 1

// Filters – row 2 aligned to the right
print '<div class="gantt-filters">';

// Default view selector (row 1, left side)
print '<select id="gantt-scale-default" class="flat gantt-filter-select">';
print '<option value="">'.$langs->trans('ChooseDefaultView').$scale_printed.'</option>';
print '<option value="day">'.$langs->trans('Day').'</option>';
print '<option value="week">'.$langs->trans('Week').'</option>';
print '<option value="month">'.$langs->trans('Month').'</option>';
print '</select>';

// Status filter (Dolibarr standard statuses)
print '<select id="gantt-filter-status" class="flat gantt-filter-select">';
print '<option value="">'.$langs->trans('AllStatuses').'</option>';
print '<option value="0">'.$langs->trans('Draft').'</option>';
print '<option value="1">'.$langs->trans('ValidatedToProduce').'</option>';
print '<option value="2">'.$langs->trans('InProgress').'</option>';
print '<option value="3">'.$langs->trans('StatusMOProduced').'</option>';
print '<option value="9">'.$langs->trans('Canceled').'</option>';
print '</select>';

// Planning state filter
print '<select id="gantt-filter-planning" class="flat gantt-filter-select">';
print '<option value="">'.$langs->trans('AllPlanningStates').'</option>';
print '<option value="unplanned">'.$langs->trans('PlanningUnplanned').'</option>';
print '<option value="planned">'.$langs->trans('PlanningPlanned').'</option>';
print '<option value="running">'.$langs->trans('PlanningRunning').'</option>';
print '<option value="overdue">'.$langs->trans('PlanningOverdue').'</option>';
print '<option value="finished">'.$langs->trans('PlanningFinished').'</option>';
print '</select>';

// Machine filter (only rendered when Workstation module is active)
print '<select id="gantt-filter-machine" class="flat gantt-filter-select"'.($has_workstation ? '' : ' style="display:none;"').'>';
print '<option value="">'.$langs->trans('AllMachines').'</option>';
foreach ($machines_list as $m) {
	print '<option value="'.dol_escape_htmltag($m).'">'.dol_escape_htmltag($m).'</option>';
}
print '</select>';

// Clear filters
print '<button type="button" id="gantt-clear-filters" class="button smallpadding">'.$langs->trans('ClearFilters').'</button>';

print '</div>'; // end filters
print '</div>'; // end toolbar

// ---- GANTT CONTAINER ----

print '<div id="gantt-wrap">';
print '<div id="gantt-inner">';

print '<div id="gantt-header-row">';
print '<div id="gantt-left-header">';
print dol_escape_htmltag($langs->trans('Ref')).' / '.$langs->trans('Product');
print '</div>';
print '<div id="gantt-days-header"></div>';
print '</div>'; // header-row

print '<div id="gantt-rows-area"></div>';
print '<div id="gantt-today-line"></div>';

print '</div>'; // gantt-inner
print '</div>'; // gantt-wrap

// Empty message
print '<div id="gantt-empty">';
print '<span class="fa fa-calendar-o fa-2x"></span>';
print $langs->trans('GanttNoOrders');
print '</div>';

// ---- LEGEND ----
print '<div class="gantt-legend">';
print '<strong>'.$langs->trans('Legend').':</strong>';
print '<span><span class="gantt-legend-dot gantt-legend-dot-unplanned"></span> '.$langs->trans('PlanningUnplanned').'</span>';
print '<span><span class="gantt-legend-dot gantt-legend-dot-planned"></span> '.$langs->trans('PlanningPlanned').'</span>';
print '<span><span class="gantt-legend-dot gantt-legend-dot-running"></span> '.$langs->trans('PlanningRunning').'</span>';
print '<span><span class="gantt-legend-dot gantt-legend-dot-overdue"></span> '.$langs->trans('PlanningOverdue').'</span>';
print '<span><span class="gantt-legend-dot gantt-legend-dot-finished"></span> '.$langs->trans('PlanningFinished').'</span>';
print '</div>';

// ---- TOOLTIP ----
print '<div id="gantt-tooltip"></div>';

// ---- CSS ----
print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/mrpgantt/css/mrpgantt.css.php', 1).'">';

// ---- JAVASCRIPT ----
$js_orders_json    = json_encode($js_orders, JSON_HEX_TAG | JSON_HEX_AMP);
$js_mo_card_url    = json_encode($mo_card_url);
$js_period_start   = (int) $period_start_ts;
$js_days           = (int) $days_in_period;
$js_now            = (int) $now;
$js_scale          = json_encode($scale);
$js_base_url       = json_encode($base_url);
// Decode HTML entities so accented chars (é, è, à…) reach JS as real UTF-8,
// not as &eacute; which esc() would re-escape into &amp;eacute;
$jt = function ($key) use ($langs) {
	return html_entity_decode($langs->trans($key), ENT_QUOTES | ENT_HTML5, 'UTF-8');
};

$js_label_ref      = json_encode($jt('Ref'));
$js_label_product  = json_encode($jt('Product'));
$js_label_status   = json_encode($jt('Status'));
$js_label_start    = json_encode($jt('DateStart'));
$js_label_end      = json_encode($jt('DateEnd'));
$js_label_progress = json_encode($jt('Progress'));
$js_label_machine  = json_encode($jt('Machine'));
$js_dow_short = json_encode(array(
	$jt('DowMon'), $jt('DowTue'), $jt('DowWed'), $jt('DowThu'),
	$jt('DowFri'), $jt('DowSat'), $jt('DowSun'),
));
$js_mon_short = json_encode(array(
	'', // index 0 unused
	$jt('MonJan'), $jt('MonFeb'), $jt('MonMar'), $jt('MonApr'),
	$jt('MonMay'), $jt('MonJun'), $jt('MonJul'), $jt('MonAug'),
	$jt('MonSep'), $jt('MonOct'), $jt('MonNov'), $jt('MonDec'),
));
$js_label_none     = json_encode($jt('None'));
$js_label_nostart  = json_encode($jt('NoStartDate'));
$js_label_noend    = json_encode($jt('NoEndDate'));
$js_label_noorders = json_encode($jt('GanttNoOrders'));
$js_status_labels  = json_encode(array(
	0 => $jt('Draft'),
	1 => $jt('ValidatedToProduce'),
	2 => $jt('InProgress'),
	3 => $jt('StatusMOProduced'),
	9 => $jt('Canceled'),
));
$js_planning_labels = json_encode(array(
	'unplanned' => $jt('PlanningUnplanned'),
	'planned'   => $jt('PlanningPlanned'),
	'running'   => $jt('PlanningRunning'),
	'overdue'   => $jt('PlanningOverdue'),
	'finished'  => $jt('PlanningFinished'),
));

print <<<JSEOF
<script>
window.GANTT_DATA = {
	orders:         $js_orders_json,
	moCardUrl:      $js_mo_card_url,
	periodStart:    $js_period_start,
	daysInPeriod:   $js_days,
	scale:          $js_scale,
	baseUrl:        $js_base_url,
	statusLabels:   $js_status_labels,
	planningLabels: $js_planning_labels,
	dowShort:       $js_dow_short,
	monShort:       $js_mon_short,
	lbl: {
		ref:      $js_label_ref,
		product:  $js_label_product,
		status:   $js_label_status,
		start:    $js_label_start,
		end:      $js_label_end,
		progress: $js_label_progress,
		machine:  $js_label_machine,
		none:     $js_label_none,
		nostart:  $js_label_nostart,
		noend:    $js_label_noend,
		noorders: $js_label_noorders
	}
};
</script>
JSEOF;
print '<script src="'.dol_buildpath('/mrpgantt/js/mrpgantt.js.php', 1).'"></script>';

llxFooter();
$db->close();
