<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2025  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2026		LEVEL IT				 <info@levelit.com.ar>
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
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    mrpgantt/js/mrpgantt.js.php
 * \ingroup mrpgantt
 * \brief   JavaScript file for module Mrpgantt.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
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

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module Mrpgantt */
(function () {
	var cfg            = window.GANTT_DATA || {};
	var ALL_ORDERS     = cfg.orders || [];
	var MO_CARD_URL    = cfg.moCardUrl || '';
	var PERIOD_START   = cfg.periodStart || 0;
	var DAYS_IN_PERIOD = cfg.daysInPeriod || 30;
	var NOW_TS         = Math.floor(Date.now() / 1000);
	var SCALE          = cfg.scale || 'month';
	var BASE_URL       = cfg.baseUrl || '';
	var STATUS_LABELS  = cfg.statusLabels || {};
	var PLANNING_COLORS = {
		unplanned: '#9E9E9E',
		planned:   '#42A5F5',
		running:   '#4CAF50',
		overdue:   '#F44336',
		finished:  '#7E57C2',
	};
	var LBL = cfg.lbl || {};

	var PERIOD_END = PERIOD_START + DAYS_IN_PERIOD * 86400;

	var DOW_SHORT = cfg.dowShort || ['Lun','Mar','Mie','Jue','Vie','Sab','Dom'];
	var MON_SHORT = cfg.monShort || ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

	function tsToDateStr(ts) {
		if (!ts) return LBL.none;
		var d = new Date(ts * 1000);
		var date = d.getUTCDate().toString().padStart(2,'0') + '/' +
			(d.getUTCMonth()+1).toString().padStart(2,'0') + '/' + d.getUTCFullYear();
		var time = d.getUTCHours().toString().padStart(2,'0') + ':' + d.getUTCMinutes().toString().padStart(2,'0');
		return date + ' ' + time;
	}

	function getDayOfWeek(ts) {
		var d = new Date(ts * 1000);
		return (d.getUTCDay() + 6) % 7;
	}

	function isWeekend(ts) {
		var dow = getDayOfWeek(ts);
		return dow === 5 || dow === 6;
	}

	function getPlanningState(order) {
		if (order.status === 0)                         return 'unplanned';
		if (!order.ts_start || !order.ts_end)           return 'unplanned';
		if (order.status === 3 || order.status === 9)   return 'finished';
		if (order.ts_end < NOW_TS)                      return 'overdue';
		if (order.status === 2)                         return 'running';
		return 'planned';
	}

	function getBarColor(order) {
		return PLANNING_COLORS[getPlanningState(order)] || '#9E9E9E';
	}

	// Returns the bar start timestamp snapped to the scale grid.
	// Month: 2 halves (AM → day start, PM → noon).
	// Week:  3 thirds (00-08 → start, 08-16 → +8h, 16-24 → +16h).
	function getSnapStart(ts) {
		var dayOffset = Math.floor((ts - PERIOD_START) / 86400);
		var dayStart  = PERIOD_START + dayOffset * 86400;
		var t         = ts - dayStart; // seconds into the day
		if (SCALE === 'month') {
			return (t < 43200) ? dayStart : dayStart + 43200;
		}
		// week
		if (t <= 8 * 3600)  return dayStart;
		if (t <= 16 * 3600) return dayStart + 8 * 3600;
		return dayStart + 16 * 3600;
	}

	// Returns the bar end timestamp snapped to the scale grid.
	// Month: end AM (≤ noon) → noon; end PM → end of day.
	// Week:  end in 1st third → 08:00; 2nd → 16:00; 3rd → end of day.
	// Exactly 12:00 PM end → noon (first rule wins).
	function getSnapEnd(ts) {
		var dayOffset = Math.floor((ts - PERIOD_START) / 86400);
		var dayStart  = PERIOD_START + dayOffset * 86400;
		var t         = ts - dayStart;
		if (SCALE === 'month') {
			return (t <= 43200) ? dayStart + 43200 : dayStart + 86400;
		}
		// week
		if (t <= 8 * 3600)  return dayStart + 8 * 3600;
		if (t <= 16 * 3600) return dayStart + 16 * 3600;
		return dayStart + 86400;
	}

	function filterOrders() {
		var st  = document.getElementById('gantt-filter-status').value;
		var mac = document.getElementById('gantt-filter-machine').value;
		var pl  = document.getElementById('gantt-filter-planning').value;
		return ALL_ORDERS.filter(function (o) {
			if (st !== '' && o.status !== parseInt(st, 10)) return false;
			if (mac && o.machine !== mac)                   return false;
			if (pl  && getPlanningState(o) !== pl)          return false;
			return true;
		});
	}

	function render() {
		var orders = filterOrders();
		var url = new URL(window.location.href);
		var st  = document.getElementById('gantt-filter-status').value;
		var mac = document.getElementById('gantt-filter-machine').value;
		var pl  = document.getElementById('gantt-filter-planning').value;
		if (st)  url.searchParams.set('filter_status',   st);   else url.searchParams.delete('filter_status');
		if (mac) url.searchParams.set('filter_machine',  mac);  else url.searchParams.delete('filter_machine');
		if (pl)  url.searchParams.set('filter_planning', pl);   else url.searchParams.delete('filter_planning');
		history.replaceState(null, '', url.toString());
		renderHeader();
		renderRows(orders);
		renderTodayLine();
	}

	function renderHeader() {
		var header = document.getElementById('gantt-days-header');
		header.innerHTML = '';

		for (var d = 0; d < DAYS_IN_PERIOD; d++) {
			var ts     = PERIOD_START + d * 86400;
			var dt     = new Date(ts * 1000);
			var dom    = dt.getUTCDate();
			var dow    = getDayOfWeek(ts);
			var isWE   = isWeekend(ts);
			var isToday = (ts <= NOW_TS && NOW_TS < ts + 86400);

			var cell = document.createElement('div');
			cell.className = 'gantt-day-col' + (isWE ? ' weekend' : '') + (isToday ? ' today-col' : '');
			cell.style.flex = '1';
			cell.style.minWidth = '30px';

			if (SCALE === 'day') {
				var mn = MON_SHORT[dt.getUTCMonth() + 1] || '';
				cell.innerHTML = '<span style="font-size:1.1em;font-weight:bold;">' + DOW_SHORT[dow] + ' ' + dom + ' ' + mn + '</span>';
			} else if (SCALE === 'week') {
				cell.innerHTML = '<span>' + DOW_SHORT[dow] + '</span><span style="font-weight:bold;">' + dom + '</span>';
			} else {
				cell.innerHTML = '<span>' + dom + '</span>';
			}
			header.appendChild(cell);
		}
	}

	function renderRows(orders) {
		var rowsArea = document.getElementById('gantt-rows-area');
		rowsArea.innerHTML = '';

		var visibleCount = 0;

		orders.forEach(function (order) {
			var tsStart = order.ts_start || order.ts_creation;
			if (!tsStart || tsStart >= PERIOD_END) return;
			if (order.ts_end !== null) {
				if (SCALE === 'day') {
					// Day view: strict — exclude orders that ended before this day starts
					if (order.ts_end < PERIOD_START) return;
				} else {
					// Month/week: tolerate a day's buffer for date-only midnight values
					if (order.ts_end + 86400 <= PERIOD_START) return;
				}
			}

			visibleCount++;

			var row = document.createElement('div');
			row.className = 'gantt-data-row';

			var lbl = document.createElement('div');
			lbl.className = 'gantt-row-label';
			lbl.setAttribute('title', order.ref + ' / ' + order.product_label);
			lbl.innerHTML =
				'<strong style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">' + esc(order.ref) + '</strong>' +
				'<span style="color:#888;font-size:0.82em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">' + esc(order.product_label || order.product_ref) + '</span>';
			row.appendChild(lbl);

			var cells = document.createElement('div');
			cells.className = 'gantt-row-cells';

			for (var d = 0; d < DAYS_IN_PERIOD; d++) {
				var ts = PERIOD_START + d * 86400;
				var bg = document.createElement('div');
				bg.className = 'gantt-cell-bg' +
					(isWeekend(ts) ? ' weekend' : '') +
					((ts <= NOW_TS && NOW_TS < ts + 86400) ? ' today-col' : '');
				cells.appendChild(bg);
			}

			if (tsStart) {
				var barStart, barEnd;
				if (SCALE === 'day') {
					// Exact positioning within the day, clamped to period boundaries.
					barStart = Math.max(tsStart, PERIOD_START);
					barEnd   = order.ts_end ? Math.min(order.ts_end, PERIOD_END) : PERIOD_END;
					// End exactly at midnight of this day (e.g. "13/06 00:00"): show
					// a minimal 30-min slot so at least the first square is visible.
					if (barEnd <= barStart && barStart < PERIOD_END) {
						barEnd = Math.min(barStart + 1800, PERIOD_END);
					}
				} else {
					// Month: 2 halves per day. Week: 3 thirds per day.
					barStart = Math.max(getSnapStart(tsStart), PERIOD_START);
					barEnd   = order.ts_end
						? Math.min(getSnapEnd(order.ts_end), PERIOD_END)
						: PERIOD_END;
				}

				if (barStart < PERIOD_END && barEnd > barStart) {
					var leftPct  = ((barStart - PERIOD_START) / (PERIOD_END - PERIOD_START)) * 100;
					var widthPct = ((barEnd   - barStart)     / (PERIOD_END - PERIOD_START)) * 100;
					leftPct  = Math.max(0, Math.min(100, leftPct));
					widthPct = Math.max(0.5, Math.min(100 - leftPct, widthPct));

					var ps    = getPlanningState(order);
					var color = getBarColor(order);

					var bar = document.createElement('div');
					bar.className = 'gantt-bar';
					bar.style.left       = leftPct + '%';
					bar.style.width      = widthPct + '%';
					bar.style.background = color;
					bar.style.minWidth   = '8px';

					if (ps === 'unplanned') {
						var stripe = document.createElement('div');
						stripe.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;background:repeating-linear-gradient(-45deg,transparent,transparent 5px,rgba(255,255,255,0.22) 5px,rgba(255,255,255,0.22) 10px);pointer-events:none;';
						bar.insertBefore(stripe, bar.firstChild);
						bar.style.borderRight = '4px dashed rgba(255,255,255,0.9)';
						bar.style.borderTopRightRadius = '0';
						bar.style.borderBottomRightRadius = '0';
					}

					if ((ps === 'planned' || ps === 'running') && order.progress > 0 && order.progress < 100) {
						var prog = document.createElement('div');
						prog.className = 'gantt-bar-progress';
						prog.style.width = order.progress + '%';
						prog.style.background = 'rgba(255,255,255,0.28)';
						bar.appendChild(prog);
					}

					var barLbl = document.createElement('span');
					barLbl.className = 'gantt-bar-label';
					barLbl.textContent = order.ref + (order.progress > 0 && order.progress < 100 ? ' (' + order.progress + '%)' : '');
					bar.appendChild(barLbl);

					bar.addEventListener('click', function (o) {
						return function () { window.open(MO_CARD_URL + '?id=' + o.id, '_blank'); };
					}(order));
					bar.addEventListener('mouseenter', function (o) {
						return function (e) { showTooltip(e, o); };
					}(order));
					bar.addEventListener('mousemove', function (e) { moveTooltip(e); });
					bar.addEventListener('mouseleave', hideTooltip);

					cells.appendChild(bar);
				}
			}

			row.appendChild(cells);
			rowsArea.appendChild(row);
		});

		document.getElementById('gantt-empty').style.display = (visibleCount === 0) ? 'block' : 'none';
		document.getElementById('gantt-wrap').style.display  = (visibleCount === 0) ? 'none'  : 'block';
	}

	function renderTodayLine() {
		var line = document.getElementById('gantt-today-line');
		if (SCALE !== 'day' && NOW_TS >= PERIOD_START && NOW_TS < PERIOD_END) {
			var fraction = (NOW_TS - PERIOD_START) / (PERIOD_END - PERIOD_START);
			fraction = Math.max(0, Math.min(1, fraction));
			var innerW   = document.getElementById('gantt-inner').offsetWidth;
			var cellsW   = innerW - 140;
			var rowsArea = document.getElementById('gantt-rows-area');
			var headerH  = document.getElementById('gantt-header-row').offsetHeight;
			line.style.left    = (140 + fraction * cellsW) + 'px';
			line.style.top     = headerH + 'px';
			line.style.height  = rowsArea.offsetHeight + 'px';
			line.style.display = 'block';
		} else {
			line.style.display = 'none';
		}
	}

	function showTooltip(e, order) {
		var tt = document.getElementById('gantt-tooltip');
		var statusLabel = STATUS_LABELS[order.status] || order.status;
		tt.innerHTML =
			'<table style="border-collapse:collapse;">' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.ref + ':</td><td><strong>' + esc(order.ref) + '</strong></td></tr>' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.product + ':</td><td>' + esc(order.product_label || order.product_ref) + '</td></tr>' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.status + ':</td><td>' + esc(statusLabel) + '</td></tr>' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.start + ':</td><td>' + (order.ts_start ? esc(order.date_start_str || tsToDateStr(order.ts_start)) : LBL.nostart) + '</td></tr>' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.end + ':</td><td>' + (order.date_end_str ? esc(order.date_end_str) : (order.ts_end ? tsToDateStr(order.ts_end) : LBL.noend)) + '</td></tr>' +
			'<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.progress + ':</td><td>' + order.progress + '%</td></tr>' +
			(order.machine ? '<tr><td style="padding:2px 6px 2px 0;color:#888;">' + LBL.machine + ':</td><td>' + esc(order.machine) + '</td></tr>' : '') +
			'</table>';
		tt.style.display = 'block';
		moveTooltip(e);
	}

	function moveTooltip(e) {
		var tt = document.getElementById('gantt-tooltip');
		var x = e.clientX + 14, y = e.clientY + 14;
		var w = tt.offsetWidth,  h = tt.offsetHeight;
		if (x + w > window.innerWidth  - 10) x = e.clientX - w - 14;
		if (y + h > window.innerHeight - 10) y = e.clientY - h - 14;
		tt.style.left = x + 'px';
		tt.style.top  = y + 'px';
	}

	function hideTooltip() {
		document.getElementById('gantt-tooltip').style.display = 'none';
	}

	function esc(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	document.getElementById('gantt-scale-default').addEventListener('change', function () {
		var val = this.value;
		if (!val) return;
		window.location = BASE_URL + '?scale=' + encodeURIComponent(val) + '&set_default=1';
	});

	document.getElementById('gantt-filter-status').addEventListener('change', render);
	document.getElementById('gantt-filter-machine').addEventListener('change', render);
	document.getElementById('gantt-filter-planning').addEventListener('change', render);
	document.getElementById('gantt-clear-filters').addEventListener('click', function () {
		document.getElementById('gantt-filter-status').value   = '';
		document.getElementById('gantt-filter-machine').value  = '';
		document.getElementById('gantt-filter-planning').value = '';
		render();
	});

	(function () {
		var url = new URL(window.location.href);
		var st  = url.searchParams.get('filter_status');
		var mac = url.searchParams.get('filter_machine');
		var pl  = url.searchParams.get('filter_planning');
		if (st)  document.getElementById('gantt-filter-status').value   = st;
		if (mac) document.getElementById('gantt-filter-machine').value  = mac;
		if (pl)  document.getElementById('gantt-filter-planning').value = pl;
	})();

	(function () {
		function navigateToDate(date) {
			window.location = BASE_URL + '?scale=day&year=' + date.getFullYear() + '&month=' + (date.getMonth() + 1) + '&day=' + date.getDate();
		}

		// Button click: read date directly from the datepicker widget
		var btn = document.getElementById('gantt-goto-btn');
		if (btn) {
			btn.addEventListener('click', function () {
				var $inp = $('#ganttgoto');
				if (!$inp.length) return;
				var date = $inp.datepicker('getDate');
				if (date) navigateToDate(date);
			});
		}

		// Auto-navigate on calendar select. setTimeout(0) ensures we run after
		// Dolibarr's own $(function(){}) blocks have finished initializing the widget.
		$(function () {
			setTimeout(function () {
				var $inp = $('#ganttgoto');
				if ($inp.length && typeof $.fn.datepicker !== 'undefined') {
					try {
						$inp.datepicker('option', 'onSelect', function () {
							var date = $(this).datepicker('getDate');
							if (date) navigateToDate(date);
						});
						$inp.on('keydown', function (e) {
							if (e.which === 13) {
								e.preventDefault();
								$(this).datepicker('setDate', $(this).val());
								var date = $(this).datepicker('getDate');
								if (date) navigateToDate(date);
							}
						});
					} catch (e) {}
				}
			}, 0);
		});
	})();

	render();
})();
