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
 * LabQC Pro shared helpers.
 * Copyright (C) 2026 PanciucSoft
 */

/**
 * Check a LabQC Pro permission while preserving full access for Dolibarr administrators.
 *
 * @param User   $user      Dolibarr user object
 * @param string $resource  Permission resource
 * @param string $action    Permission action
 * @return bool
 */
function labqcproUserCan($user, $resource, $action)
{
    if (!empty($user->admin)) {
        return true;
    }

    if (!is_object($user) || !method_exists($user, 'hasRight')) {
        return false;
    }

    return (bool) $user->hasRight('labqcpro', $resource, $action);
}

/**
 * Render the shared LabQC page heading. Presentation only; no permissions or database access.
 *
 * @param string $title Translated page name.
 * @param string $subtitle Translated explanation.
 * @return string Escaped HTML.
 */
function labqcproPageHero($title, $subtitle)
{
    $titleSafe = dol_escape_htmltag(html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $subtitleSafe = dol_escape_htmltag(html_entity_decode((string) $subtitle, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return '<header class="labqc-page-hero">'
        .'<div class="labqc-page-hero-copy"><span class="labqc-page-eyebrow">PANCIUCSOFT / LABQC PRO</span>'
        .'<h1>'.$titleSafe.'</h1><p>'.$subtitleSafe.'</p></div>'
        .'</header>';
}
