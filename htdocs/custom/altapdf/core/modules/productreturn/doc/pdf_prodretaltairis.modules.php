<?php

/* Copyright (C) 2023 Noé Cendrier	<noe.cendrier@altairis.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

dol_include_once('/altapdf/core/modules/pdf_altairis.modules.php');

/**
 *     \class      pdf_retaltairis
 *     \brief      Classe permettant de generer les bons de retour au modele altairis_return
 */
class pdf_prodretaltairis extends pdf_altairis
{
	public $no_specials;
    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db);

        $this->heightforinfotot = 0;
        $this->heightforavisselling = 0;
        $this->heightforavisrental = 0;
        $this->no_specials = true;
        $this->heightforfreetext = 0;
    }

    public function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {
        return $posy;
    }
}
