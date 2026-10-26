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
 * \file    mrpgantt/css/mrpgantt.css.php
 * \ingroup mrpgantt
 * \brief   CSS file for module Mrpgantt.
 */

//if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (!defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
//if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', 1);		// Should be disable only for special situation
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

?>

/* CSS library of module Mrpgantt */

/* ---- Small screen warning ---- */
.gantt-small-screen-warning {
	display:none;padding:20px;text-align:center;
	background:#fff3e0;border:1px solid #ff9800;border-radius:4px;margin-bottom:10px;
}
@media (max-width:1279px) {
	.gantt-small-screen-warning { display:block !important; }
}

/* ---- Toolbar ---- */
.gantt-toolbar {
	display:flex;flex-direction:column;gap:6px;
	margin-bottom:12px;padding:8px 10px;
	background:#f5f5f5;border:1px solid #e0e0e0;border-radius:4px;box-sizing:border-box;
}
.gantt-toolbar-row   { display:flex;flex-wrap:wrap;align-items:center;gap:8px; }
.gantt-scale-btns    { display:flex;gap:4px; }
.gantt-scale-active  { font-weight:bold !important;background:#1c7be1 !important;color:#fff !important;cursor:default; }
.gantt-nav           { display:flex;align-items:center;gap:4px; }
#gantt-goto-date     { padding:3px 5px;height:26px;cursor:pointer; }
.gantt-period-label  { margin:0 8px;font-size:1.05em; }
.gantt-filters       { display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-start;align-items:center; }
.gantt-filter-select { padding:3px 6px; }

/* ---- Gantt wrap + inner ---- */
#gantt-wrap  { width:100%;box-sizing:border-box;border:1px solid #ddd;border-radius:4px;overflow-x:auto;background:#fff; }
#gantt-inner { position:relative;display:inline-block;min-width:100%; }

/* ---- Header row ---- */
#gantt-header-row {
	display:flex;position:sticky;top:0;z-index:15;
	background:#f5f5f5;border-bottom:1px solid #ddd;
}
#gantt-left-header {
	position:sticky;left:0;width:140px;min-width:140px;flex-shrink:0;z-index:20;
	background:#f5f5f5;border-right:2px solid #ddd;box-sizing:border-box;
	display:flex;align-items:center;padding:0 10px;font-weight:bold;font-size:0.9em;
}
#gantt-days-header { flex:1;min-width:0;display:flex; }

/* ---- Today line ---- */
#gantt-today-line {
	position:absolute;top:0;bottom:0;width:2px;
	background:#E53935;opacity:0.85;pointer-events:none;display:none;z-index:5;
}

/* ---- Empty state ---- */
#gantt-empty {
	display:none;padding:24px;text-align:center;color:#888;
	background:#fafafa;border:1px solid #eee;border-top:none;
}
#gantt-empty .fa { display:block;margin-bottom:8px; }

/* ---- Data rows ---- */
.gantt-data-row { display:flex;border-bottom:1px solid #f0f0f0; }
.gantt-data-row:hover .gantt-row-label { background:#f9f9f9; }
.gantt-data-row:hover .gantt-row-cells { background:#f9f9f9; }

/* ---- Left label (sticky column) ---- */
.gantt-row-label {
	position:sticky;left:0;width:140px;min-width:140px;flex-shrink:0;z-index:2;
	background:#fff;border-right:2px solid #ddd;box-sizing:border-box;
	min-height:48px;line-height:normal;padding:4px 10px;
	overflow:hidden;font-size:0.85em;cursor:default;
	display:flex;flex-direction:column;justify-content:center;
}

/* ---- Right cells area ---- */
.gantt-row-cells { flex:1;min-width:0;min-height:48px;position:relative;display:flex; }

/* ---- Day header cells ---- */
.gantt-day-col {
	flex:1;min-width:28px;text-align:center;font-size:0.78em;color:#555;
	border-right:1px solid #f0f0f0;padding:0 2px;display:flex;
	align-items:center;justify-content:center;flex-direction:column;
}
.gantt-day-col.weekend   { background:#fafafa;color:#aaa; }
.gantt-day-col.today-col { background:#fff8e1;color:#f57c00;font-weight:bold; }

/* ---- Background day cells inside each row ---- */
.gantt-cell-bg           { flex:1;min-width:28px;height:100%;border-right:1px solid #f0f0f0; }
.gantt-cell-bg.weekend   { background:#fafafa; }
.gantt-cell-bg.today-col { background:#fff8e1; }

/* ---- Gantt bar ---- */
.gantt-bar {
	position:absolute;top:12px;height:24px;border-radius:3px;cursor:pointer;
	overflow:hidden;display:flex;align-items:center;min-width:8px;
	box-shadow:0 1px 3px rgba(0,0,0,0.2);
}
.gantt-bar-progress {
	position:absolute;top:0;left:0;height:100%;background:rgba(255,255,255,0.25);
	transition:width 0.3s;
}
.gantt-bar-label {
	position:relative;padding:0 6px;font-size:0.75em;color:#fff;white-space:nowrap;
	overflow:hidden;text-overflow:ellipsis;font-weight:500;text-shadow:0 1px 1px rgba(0,0,0,0.3);
}

/* ---- Legend ---- */
.gantt-legend        { display:flex;flex-wrap:wrap;gap:16px;margin-top:12px;padding:10px;background:#fafafa;border:1px solid #eee;border-radius:4px;font-size:0.88em; }
.gantt-legend strong { align-self:center; }
.gantt-legend-dot    { display:inline-block;width:16px;height:12px;border-radius:2px;vertical-align:middle; }
.gantt-legend-dot-unplanned {
	width:20px;background:#9E9E9E;
	background-image:repeating-linear-gradient(-45deg,transparent,transparent 4px,rgba(255,255,255,0.3) 4px,rgba(255,255,255,0.3) 8px);
	border-right:4px dashed rgba(255,255,255,0.9);border-radius:2px 0 0 2px;
	box-shadow:0 1px 2px rgba(0,0,0,0.2);
}
.gantt-legend-dot-planned  { background:#42A5F5; }
.gantt-legend-dot-running  { background:#4CAF50; }
.gantt-legend-dot-overdue  { background:#F44336; }
.gantt-legend-dot-finished { background:#7E57C2; }

/* ---- Datepicker: float above sticky Gantt elements ---- */
.ui-datepicker { z-index: 10000 !important; }

/* ---- Tooltip ---- */
#gantt-tooltip {
	position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #aaa;
	border-radius:4px;padding:10px 14px;box-shadow:2px 4px 12px rgba(0,0,0,0.15);
	font-size:0.88em;max-width:300px;pointer-events:none;
}
