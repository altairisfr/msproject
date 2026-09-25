<?php
/* Copyright (C) 2014-2020		Charlene Benke	<charlene@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

print '<!-- BEGIN PHP TEMPLATE -->';

$langs = $GLOBALS['langs'];
$db = $GLOBALS['db'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("restock@restock");

foreach ($linkedObjectBlock as $key => $objectlink) {

	print '<tr class="oddeven" >';
	
	print '<td>'.$langs->trans("Restock").'</td>';
	print '<td align="left">'.$objectlink->getNomUrl(1)."</td>";
	print '<td align="left">'.$objectlink->label.'</td>';
	print '<td align="center">'.dol_print_date($objectlink->date_creation, 'day').'</td>';
	print '<td align="right">'.count($objectlink->lines).'</td>';
	print '<td align="right">'.$objectlink->getLibStatut(3).'</td>';
	print '<td class="right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key.'">';
	print img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';

	print '</tr>'."\n";
}

print '<!-- END PHP TEMPLATE -->'."\n";