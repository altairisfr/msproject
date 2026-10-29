<?php

dol_include_once('/altapdf/core/modules/pdf_altairis.modules.php');

/**
 *     \class      pdf_visualsch_propal
 *     \brief      Classe permettant de generer les propales au modele visualsch_propal
 */
class pdf_expaltairis extends pdf_altairis
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

        $this->heightforinfotot = 50;
        $this->heightforavisselling = 0;
        $this->heightforavisrental = 0;
        $this->no_specials = true;
    }

    public function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {

        global $conf, $mysoc;

        // Show payments conditions
        dol_include_once('/commande/class/commande.class.php');
        $commande = new Commande($this->db);
        $commande->fetch($object->origin_id);

        if ($commande->cond_reglement_code || $commande->cond_reglement) {
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("PaymentConditions").' :';
            $pdf->MultiCell(33, 4, $titre, 0, 'L');

            $pdf->SetFont('', '', $this->default_font_size - 3);
            $pdf->SetXY(35 + $this->marge_gauche, $posy);
            $lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition".$commande->cond_reglement_code) != ('PaymentCondition'.$commande->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$commande->cond_reglement_code) : $outputlangs->convToOutputCharset($commande->cond_reglement_doc);
            $lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
            $pdf->MultiCell(65, 4, $lib_condition_paiement, 0, 'L');

            $posy = $pdf->GetY() + 3;
        }

        $this->curY = 234 - $this->heightforfreetext;
        $deltax = $this->marge_gauche;

        $pdf->SetFont('', 'B', $this->default_font_size - 1);
        $pdf->SetXY($deltax, $this->curY - 4);
        $titre = $outputlangs->transnoentities($mysoc->name);
        $pdf->MultiCell(80, 5, $titre, 0, 'L', 0);

        $pdf->RoundedRect($deltax, $this->curY, 82, 45, 2, $round_corner = '1111', 'S', $this->style, '');
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->SetXY($deltax, $this->curY + 3);
        $pdf->MultiCell(80, 5, $outputlangs->transnoentities('Name'), 0, 'L', 0);
        $pdf->SetFont('', '', $this->default_font_size - 2);
        $pdf->SetXY($deltax, $this->curY + 9);
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities('Date'), 0, 'L', 0);
        $pdf->SetXY($deltax, $this->curY + 15);
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER3'), 0, 'L', 0);

        $this->curY = 234 - $this->heightforfreetext;
        $deltax = $this->marge_gauche + 108;

        $pdf->SetFont('', 'B', $this->default_font_size - 1);
        $pdf->SetXY($deltax, $this->curY - 4);
        $titre = $outputlangs->transnoentities($object->thirdparty->name);
        $pdf->MultiCell(80, 5, $titre, 0, 'L', 0);


        $pdf->RoundedRect($deltax, $this->curY, 82, 45, 2, $round_corner = '1111', 'S', $this->style, '');
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->SetXY($deltax, $this->curY + 3);
        $pdf->MultiCell(80, 5, $outputlangs->transnoentities('Name'), 0, 'L', 0);
        $pdf->SetFont('', '', $this->default_font_size - 2);
        $pdf->SetXY($deltax, $this->curY + 9);
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities('Date'), 0, 'L', 0);
        $pdf->SetXY($deltax, $this->curY + 15);
        $pdf->SetFont('', 'I', $this->default_font_size - 2);
        $pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER3'), 0, 'L', 0);

        return $posy;
    }



}
