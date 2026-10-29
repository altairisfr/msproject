<?php

dol_include_once('/altapdf/core/modules/pdf_altairis.modules.php');

/**
 *     \class      pdf_visualsch_propal
 *     \brief      Classe permettant de generer les propales au modele visualsch_propal
 */
class pdf_commfournaltairis extends pdf_altairis
{
    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $langs;

        parent::__construct($db);
    }
}
