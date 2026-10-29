<?php

/* Copyright (C) 2011-2012 	Regis Houssin  	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 	Philippe Grand 	<philippe.grand@atoo-net.com>
 * Copyright (C) 2012 		Juanjo Menent 	<jmenent@2byte.es>
 * Copyright (C) 2013		Agnes Rambaud	<contact@altairis.fr>
 * Copyright (C) 2016       Christophe Battarel    <christophe.battarel@altairis.fr>
 * Copyright (C) 2022-2025  Noé Cendrier    <noe.cendrier@altairis.fr>
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

/**
 *	\file       htdocs/custom/altapdf/core/modules/pdf_altairis.modules.php
 *  \ingroup    propale
 *  \brief      Fichier de la classe permettant de generer les propales au modele altairis
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 *     \class      pdf_altairis
 *     \brief      Classe permettant de generer les propales au modele altairis
 */
class pdf_altairis
{
    public $db;
    public $name;
    public $description;
    /**
	 * @var int	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;
    public $type;

    public $phpmin = array(7,4,0); // Minimum version of PHP required by module
    public $version = 'dolibarr';

    public $page_largeur;
    public $page_hauteur;
    public $format;
    public $marge_gauche;
    public $marge_droite;
    public $marge_haute;
    public $marge_basse;
    public $style;
	public $option_logo;
	public $module;
	public $rental_title;
	public $sell_title;
	public $bordercolor;
	public $textcolor;
	public $bgcolor;
	public $tab_top;
	public $tab_top_newpage;
	public $tva;
	public $localtax1;
	public $localtax2;
	public $atleastoneratenotnull;
	public $heightforfreetext;
	public $heightforfooter;
	public $heightforinfotot;
	public $heightforavisrental;
	public $heightforavisselling;
	public $scandir;
	public $avis_rental;
	public $avis_rental2;
	public $option_tva;
	public $option_modereg;
	public $option_condreg;
	public $option_codeproduitservice;
	public $option_multilang;
	public $option_escompte;
	public $option_credit_note;
	public $option_freetext;
	public $franchise;
	public $dashdotted;
	public $linestyle;
	public $proforma;
	public $currency_code;
	public $optionsArray;
	public $currency_rate;
	public $error;
	public $nblines;
	public $cols;
	public $newcols;
	public $curlines;
	public $nb_rental_lines;
	public $nb_sell_lines;
	public $total_warranty;
	public $total_rental;
	public $total_selling;
	public $total_warranty_cur;
	public $total_rental_cur;
	public $total_selling_cur;
	public $default_font_size;
	public $pagenb;
	public $title;
	public $nexY;
	public $iniY;
	public $curY;
	public $nb_lig_tab;
	public $next_tab_top_newpage;
	public $shipping_ht;
	public $shipping_ttc;
	public $shipping_vat;
	public $bottomlasttab;
	public $logo;
	public $header_address_blocks;
	public $header_other_blocks;
	public $xmldesc;
	public $result;
	public $corner_radius;

    public $emetteur;	// Objet societe qui emet

    public $option_draft_watermark;
    public $watermark;

    /**
	 * @var int Category of operation
	 */
	public $categoryOfOperation = -1; // unknown by default

    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bills");
        $langs->load("stocks");

        $this->db = $db;
        $this->name = "altairis";
        $this->description = $langs->trans('Modèle altairis complet');
        $this->module = "altapdf";
        $this->update_main_doc_field = 1;

        $this->rental_title = "RentalProductsLong";
        $this->sell_title = "SellProducts";

        $this->avis_rental = "AvisRental";
        $this->avis_rental2 = "AvisRental2";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['width'];
        $this->page_hauteur = $formatarray['height'];
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche = getDolGlobalString('MAIN_PDF_MARGIN_LEFT', 10);
        $this->marge_droite = getDolGlobalString('MAIN_PDF_MARGIN_RIGHT', 10);
        $this->marge_haute = getDolGlobalString('MAIN_PDF_MARGIN_TOP', 10);
        $this->marge_basse = getDolGlobalString('MAIN_PDF_MARGIN_BOTTOM', 10);

        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Affiche mode reglement
        $this->option_condreg = 1;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues
        $this->option_escompte = 1;                // Affiche si il y a eu escompte
        $this->option_credit_note = 1;             // Gere les avoirs
        $this->option_freetext = 1;				   // Support add of a personalised text
        $this->option_draft_watermark = 1;		   //Support add of a watermark on drafts
        $this->watermark = '';

        $this->franchise = !$mysoc->tva_assuj;

        $this->bordercolor = array(204,204,204); // #cccccc
        $this->dashdotted = 0;
        $this->style = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => $this->dashdotted , 'color' => $this->bordercolor);
        $this->linestyle = array('dash' => '1,1','color' => array(210,210,210));
        $this->bgcolor = array(204,204,204);
        $this->textcolor =  array(0,0,0);

        // Get source company
        $this->emetteur = $mysoc;
        if (! $this->emetteur->country_code) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2);
        }    // By default, if was not defined

        $this->tab_top = 128;
        $this->tab_top_newpage = (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD') ? 45 : 10);


        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        // commented by christophe battarel$this->atleastonediscount=0;

        $this->tab_top = 100;
        $this->tab_top_newpage = (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 : 10);

        $this->heightforinfotot = 52;	// Height reserved to output the info and total part
        $this->heightforavisrental = (getDolGlobalString('ALTAPDF_SHOW_AVISRENTAL')) ? 30 : 0;
        $this->heightforavisselling =  (getDolGlobalString('ALTAPDF_SHOW_AVISADDRESS')) ? 10 : 0;
        $this->heightforfreetext = (getDolGlobalString('MAIN_PDF_FREETEXT_HEIGHT') ? getDolGlobalString('MAIN_PDF_FREETEXT_HEIGHT') : 5);	// Height reserved to output the free text on last page
        $this->heightforfooter = $this->marge_basse + 18;	// Height reserved to output the footer (value include bottom margin)
        $this->proforma = false;

        $this->currency_code = '';
    }

    /**
     *  Function to build pdf onto disk
     *
     *  @param		CommonObject	$object				Id of object to generate
     *  @param		Translate		$outputlangs		Lang output object
     *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *  @param		HookManager		$hookmanager		Hookmanager object
     *  @return     int     		        			1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user,$langs,$conf,$hookmanager;

        if (! is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        if (getDolGlobalString('PROPOSAL_ALLOW_ONLINESIGN') && $object->element == 'propal') {
            $this->heightforinfotot += 20;
        }

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("orders");
        $outputlangs->load("products");
        if ($object->element == 'shipping') {
            $outputlangs->load("deliveries");
            $outputlangs->load("sendings");
        }
        if ($object->element == 'return_productReturn') {
            $outputlangs->loadLangs(array("return@return", "sendings"));
        }
        if ($object->element == 'productreturn') {
            $outputlangs->loadLangs(array("productreturn@productreturn", "sendings"));
        }

        $outputlangs->load("altapdf@altapdf");
        if (isModEnabled('warranty')) {
            $outputlangs->load("warranty@warranty");
        }
        if (isModEnabled('rental')) {
            $outputlangs->load("rental@rental");
        }
        if (isModEnabled('shippingcost')) {
            $outputlangs->load("shippingcost@shippingcost");
        }
        if (isModEnabled('equipement')) {
            $outputlangs->load("equipement@equipement");
        }

        // specific module file language
        if (!empty($this->module)) {
            $outputlangs->load($this->module."@".$this->module);
        }

        switch ($object->element) {
            case 'productreturn':
                $document = 'productreturn';
                $diroutput = $conf->productreturn->dir_output;
                break;
            case 'shipping':
                $document = 'expedition';
                $diroutput = $conf->expedition->dir_output.'/sending';
                break;
            case 'order_supplier':
                $document = 'order_supplier';
                $diroutput = $conf->fournisseur->dir_output.'/commande';
                $this->heightforavisselling = 0;
                break;
            default:
                $document = $object->element;
                $diroutput = $conf->$document->dir_output;
                break;
        }

        // Show Draft Watermark (filigrane)
        if ($object->statut == get_class($object)::STATUS_DRAFT) {
            if ($object->element == 'commande' && getDolGlobalString('COMMANDE_DRAFT_WATERMARK')) {
                $this->watermark = getDolGlobalString('COMMANDE_DRAFT_WATERMARK');
            } elseif ($object->element == 'shipping' && getDolGlobalString('SHIPPING_DRAFT_WATERMARK')) {
                $this->watermark = getDolGlobalString('SHIPPING_DRAFT_WATERMARK');
            } elseif ($object->element == 'facture' && getDolGlobalString('FACTURE_DRAFT_WATERMARK')) {
                $this->watermark = getDolGlobalString('FACTURE_DRAFT_WATERMARK');
            } elseif ($object->element == 'propal' && getDolGlobalString('PROPALE_DRAFT_WATERMARK')) {
                $this->watermark = getDolGlobalString('PROPALE_DRAFT_WATERMARK');
            }
        }

        if ($diroutput) {
            // load optionsArray for det extrafields
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $this->optionsArray = $extrafields->fetch_name_optionals_label($object->table_element_line);

            // recup devise (module currencybilling ou devise courante)
            $this->currency_code = $object->array_options['options_currency_code'] ?? '';
            if (empty($this->currency_code)) {
                $this->currency_rate = 1;
                $this->currency_code = $conf->currency;
            } else {	// recuperation de
                $this->currency_rate = $object->array_options['options_currency_rate'];
            }

            $object->fetch_thirdparty();

            // amounts in currency
            // fait dans le hook du module currencybilling $object->total_ht *= $this->currency_rate;
            // fait dans le hook du module currencybilling $object->total_ttc *= $this->currency_rate;

            if ($object->element == 'facture') {
                $deja_regle = $object->getSommePaiement();
                $amount_credit_notes_included = $object->getSumCreditNotesUsed();
                $amount_deposits_included = $object->getSumDepositsUsed();
                // amounts in currency
                $amount_credit_notes_included *= $this->currency_rate;
                $amount_deposits_included *= $this->currency_rate;
            }

            // Definition de $dir et $file
            if ($object->specimen) {
                $dir = $diroutput;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir = $diroutput.'/' . $objectref;
                if (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 0) {
                    // préfixé par le nom manuellement choisi
                    $file = $dir . "/" . str_replace(' ', '', getDolGlobalString('ALTAPDF_MANUAL_PREFIX_PDF')). '_' . $objectref . ".pdf";
                } elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 1) {
                    // Pas de préfix
                    $file = $dir . "/" . $objectref . ".pdf";
                } elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 2) {
                    // préfixé par le nom de la sociéte (par défaut à l'activation du module)
                    $file = $dir . "/" . str_replace(' ', '', $_SESSION["dol_company"]). '_' . $objectref . ".pdf";
                } elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 3) {
                    // préfixé par le nom du modéle PDF
                    $file = $dir . "/" . $this->name. '_' . $objectref . ".pdf";
                }
            }

            if (! file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $this->nblines = count($object->lines);

                // recherche extrafields ligne
                for ($i = 0 ; $i < $this->nblines ; $i++) {
                    if (empty($object->lines[$i]->array_options) && !empty($object->lines[$i]->id)) {
                        $object->lines[$i]->fetch_optionals($object->lines[$i]->id);
                    }
                }

                // check if optional field in desc is present or not
                $this->getXMLDescription($document);
                $this->newcols = array();

                foreach ($this->cols as $field => $col) {
                    if (!empty($col['grow_if_absent'])) {
                        for ($i = 0 ; $i < $this->nblines ; $i++) {
                            if (substr($field, 0, 16) == 'product_options_' && !empty($object->lines[$i]->fk_product)) {
                                dol_include_once('/product/class/product.class.php');
                                $productstatic = new Product($this->db);
                                $productstatic->fetch_optionals($object->lines[$i]->fk_product);
                                if (!empty($productstatic->array_options[str_replace('product_', '', $field)])) {
                                    $this->cols[$field]['present'] = 1;
                                    break;
                                }
                            } elseif (substr($field, 0, 8) == 'options_') {
                                if (!empty($object->lines[$i]->array_options[$field])) {
                                    $this->cols[$field]['present'] = 1;
                                    break;
                                }
                            } elseif (!empty($col['display_depends_on']) && !empty($object->lines[$i]->$col['display_depends_on'])) {
                                $this->cols[$field]['present'] = 1;
                                break;
                            } elseif ($field == 'remise_percent') {
                                if (! empty($object->lines[$i]->$field) && $object->lines[$i]->$field != 100) {
                                    $this->cols[$field]['present'] = 1;
                                    break;
                                }
                            } elseif (!empty($object->lines[$i]->$field)) {
                                $this->cols[$field]['present'] = 1;
                                break;
                            }
                        }
                    }
                    $this->newcols[$field] = $this->cols[$field];
                }

                // update position and length if optional field id absent in lines
                foreach ($this->cols as $field => $col) {
                    if (!empty($col['grow_if_absent']) && empty($col['present'])) {
                        // increase length of associated column
                        $this->newcols[$col['grow_if_absent']]['length'] += $col['length'];

                        // increase position of columns on the right of associated column
                        foreach ($this->newcols as $f => $c) {
                            if ($c['pos'] < $col['pos'] && ($c['pos'] > $this->newcols[$col['grow_if_absent']]['pos'] && $this->newcols[$col['grow_if_absent']]['pos'] >= 0)) {
                                $this->newcols[$f]['pos'] += $col['length'];
                            }
                        }
                        unset($this->newcols[$field]);
                    }
                }
                $this->cols = $this->newcols;


                if ($object->element == 'order_supplier') {
                    // tri des lignes et cumul des montants par devises
                    $currency_lines = array();
                    $i = 0;
                    while ($i <	$this->nblines) {
                        $line =	$object->lines[$i];
                        $currency = !empty($line->array_options['options_currency']) ? $line->array_options['options_currency'] : $conf->currency;

                        $amounts_ht = $amounts[$currency]['total_ht'] ?? 0;
                        $amounts_tva = $amounts[$currency]['total_tva'] ?? 0;
                        $amounts_localtax1 = $amounts[$currency]['total_localtax1'] ?? 0;
                        $amounts_localtax2 = $amounts[$currency]['total_localtax2'] ?? 0;
                        $amounts_ttc = $amounts[$currency]['total_ttc'] ?? 0;

                        $amounts_ht += $line->total_ht;
                        $amounts_tva += $line->total_tva;
                        $amounts_localtax1 += $line->total_localtax1;
                        $amounts_localtax2 += $line->total_localtax2;
                        $amounts_ttc += $line->total_ttc;

                        $currency_lines[$currency][$i] = $line;
                        $i++;
                    }
                    $this->curlines = array();
                    ksort($currency_lines);
                    foreach ($currency_lines as $currency => $lines) {
                        foreach ($lines as $i => $line) {
                            $this->curlines[$i] = $line;
                        }
                    }
                } else {
                    // Parcours des lignes
                    $this->nb_rental_lines = 0;
                    $this->nb_sell_lines = 0;

                    $this->total_warranty = 0;
                    $this->total_rental = 0;
                    $this->total_selling = 0;
                    $this->total_warranty_cur = 0;
                    $this->total_rental_cur = 0;
                    $this->total_selling_cur = 0;

                    if (!$object->specimen) {
                        if ($object->element == 'shipping') {
                            $orderId = $object->origin_id;
                            $order = new Commande($this->db);
                            $order->fetch_optionals($orderId);
                            $object->array_options = $order->array_options;
                        }

                        for ($i = 0 ; $i < $this->nblines ; $i++) {
                            if ($object->element == 'shipping') {
                                if ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) && $object->lines[$i]->qty != 50) {
                                    continue;
                                }

                                $orderLineId = $object->lines[$i]->fk_origin_line ?? $object->lines[$i]->origin_line_id;
                                $orderLine = new OrderLine($this->db);
                                if ($orderLineId > 0) {
                                    $orderLine->fetch($orderLineId);
                                    $orderLine->fetch_optionals($orderLineId);
                                    $object->lines[$i]->fk_product = $orderLine->fk_product;
                                    $object->lines[$i]->array_options = $orderLine->array_options;
                                }
                            }

                            // add unreturned qty value for productreturns
                            if ($object->element == 'productreturn' || $object->element == 'return_productReturn') {
                                if ($object->element == 'productreturn') {
                                    dol_include_once('/productreturn/lib/productreturn.lib.php');
                                }
                                if ($object->element == 'return_productReturn') {
                                    dol_include_once('/return/lib/return.lib.php');
                                }
                                $returns = loadReturns($object->origin_id, 0, null);
                                $object->lines[$i]->qty_not_returned = $object->lines[$i]->qty_sent - $returns[$object->lines[$i]->fk_origin_line][$object->lines[$i]->fk_product];
                            }

                            // detect rental lines
                            if (isModEnabled('rental') && !empty($object->array_options['options_rental_doc']) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                                if (!empty($object->lines[$i]->array_options['options_rental_line'])) {
                                    $this->nb_rental_lines++;
                                    if (empty($object->lines[$i]->special_code) && ($object->lines[$i]->total_ht != 0)) {
                                        $this->total_rental += $object->lines[$i]->array_options['options_rental_price'] * $object->lines[$i]->qty  * (1 - $object->lines[$i]->remise_percent / 100);
                                        // $this->total_warranty += !empty($object->lines[$i]->array_options['options_rental_warranty_price']) * $object->lines[$i]->qty;
                                        $this->total_warranty += (!empty($object->lines[$i]->array_options['options_rental_warranty_price']) ? (float) $object->lines[$i]->array_options['options_rental_warranty_price'] : 0) * $object->lines[$i]->qty;

                                        $this->total_rental_cur += ($object->lines[$i]->array_options['options_rental_price'] * $object->lines[$i]->qty  * (1 - $object->lines[$i]->remise_percent / 100) * $this->currency_rate);
                                        $this->total_warranty_cur += (!empty($object->lines[$i]->array_options['options_rental_warranty_price']) ? (float) $object->lines[$i]->array_options['options_rental_warranty_price'] : 0 * $object->lines[$i]->qty)  * $this->currency_rate;
                                    }
                                } else {
                                    if (

                                        ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) && $object->lines[$i]->qty <= 9 && isset($object->lines[$i + 1]) && !empty($object->lines[$i + 1]->array_options['options_rental_line'])) // titre

                                        ||

                                        ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) && $object->lines[$i]->qty == 99 && isset($object->lines[$i - 1]) && !empty($object->lines[$i - 1]->array_options['options_rental_line'])) // sous-total

                                        ||

                                        ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) && $object->lines[$i]->qty == 50 && $i > 1 && isset($object->lines[$i - 1]) && !empty($object->lines[$i - 1]->array_options['options_rental_line'])) // free text

                                        // ||

                                        // ($object->lines[$i]->special_code == 140015 && $i+1 == count($object->lines) && $this->total_selling == 0)

                                    ) {
                                        continue;
                                    }
                                    $this->nb_sell_lines++;
                                    if (empty($object->lines[$i]->special_code)) {
                                        $this->total_selling += $object->lines[$i]->total_ht;
                                        $this->total_selling_cur += $object->lines[$i]->total_ht * $this->currency_rate;
                                    }
                                }
                            } else {
                                $this->nb_sell_lines++;
                            }
                        }

                        if (!empty($object->array_options['options_rental_doc']) && $this->nb_rental_lines > 0 && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                            if ($object->element != 'shipping' && empty($this->no_tab_tot)) {
                                switch ($object->element) {
                                    case 'propale':
                                    case 'propal':
                                        $lineclass = 'PropaleLigne';
                                        $fk_object = 'fk_propale';
                                        break;
                                    case 'commande':
                                        $lineclass = 'OrderLine';
                                        $fk_object = 'fk_commande';
                                        break;
                                    case 'facture':
                                        $lineclass = 'FactureLigne';
                                        $fk_object = 'fk_facture';
                                        break;
                                }
                                // ajout ligne sous-total equipements pour la location
                                $newline = new $lineclass($this->db);
                                if (!empty($object->id)) {
                                    $newline->$fk_object = $object->id;
                                } else {
                                    $newline->$fk_object = $object->rowid;
                                }
                                //$newline->label = $outputlangs->transnoentities('RENTAL_TOTAL_LABEL');
                                $newline->description = $outputlangs->transnoentities('RENTAL_TOTAL_DESC');
                                $newline->total_ht = $this->total_rental;
                                $newline->special_code = 140015;
                                $newline->product_type = 9;
                                $newline->array_options = array();
                                $newline->array_options['options_rental_line'] = '1';
                                $newline->id = 1400251; // important entre autre pour fetch_optionals
                                $object->lines[$this->nblines] = $newline;
                                $this->nblines++;

                                if (getDolGlobalString('PRINT_WARRANTY_LINES')) {
                                    if (getDolGlobalString('RENTAL_WARRANTY_MANAGEMENT') && !empty($this->total_warranty)) {
                                        $newline = new $lineclass($this->db);
                                        //$newline->label = $outputlangs->transnoentities('RENTAL_WARRANTY_TOTAL_LABEL');
                                        $newline->description = $outputlangs->transnoentities('RENTAL_WARRANTY_TOTAL_DESC', $this->emetteur->name);
                                        $newline->total_ht = $this->total_warranty;
                                        $newline->special_code = 140015;
                                        $newline->product_type = 9;
                                        $newline->array_options = array();
                                        $newline->array_options['options_rental_line'] = '1';
                                        $newline->id = 1400252; // important entre autre pour fetch_optionals
                                        $object->lines[$this->nblines] = $newline;
                                        $this->nblines++;
                                    }
                                }

                                // ajout ligne sous-total vente si vente ET location
                                if ($this->nb_sell_lines > 0) {
                                    $newline = new $lineclass($this->db);
                                    //$newline->label = $outputlangs->transnoentities('SELLING_TOTAL_LABEL');
                                    $newline->description = $outputlangs->transnoentities('SELLING_TOTAL_DESC');
                                    $newline->total_ht = $this->total_selling;
                                    $newline->special_code = 140015;
                                    $newline->product_type = 9;
                                    $newline->array_options['options_rental_line'] = '0';
                                    $newline->qty = 140015; // pour éviter une confusion avec les lignes du module subtotal dans la fonction printlines
                                    $newline->id = 1400253; // important entre autre pour fetch_optionals
                                    $object->lines[$this->nblines] = $newline;
                                    $this->nblines++;
                                }

                            }
                        } else {
                            $this->tab_top -= 15;
                            $this->tab_top_newpage -= 15;
                        }
                    }
                }

                if ($object->element == 'facture') {
                    $this->heightforavisselling = 0;
                    $this->heightforavisrental = 10;
                }

                // Add pdfgeneration hook
                if (! is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file,'object' => $object,'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

                // Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $this->default_font_size = pdf_getPDFFontSize($outputlangs);
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (!getDolGlobalString('MAIN_DISABLE_FPDI') && getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
                    $tplidx = $pdf->importPage(1);
                }

                $pdf->Open();
                $this->pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                if (empty($this->title)) {
                    switch ($object->element) {
                        case 'propal':
                            $this->title = "PdfCommercialProposalTitle";
                            break;
                        case 'commande':
                            $this->title = "PdfOrderTitle";
                            break;
                        case 'shipping':
                            $this->title = "SendingSheet";
                            break;
                        case 'facture':
                            $this->title = "PdfInvoiceTitle";
                            if ($object->type == 1) {
                                $this->title = "InvoiceReplacement";
                            }
                            if ($object->type == 2) {
                                $this->title = "InvoiceAvoir";
                            }
                            if ($object->type == 3) {
                                $this->title = "InvoiceDeposit";
                            }
                            if ($object->type == 4) {
                                $this->title = "InvoiceProFormat";
                            }
                            break;
                        case 'order_supplier':
                            $this->title = "SupplierOrder";
                            break;
                        case 'productreturn':
                        case 'return_productReturn':
                            $this->title = "ProductReturn";
                            break;
                    }
                    if (!empty($object->array_options['options_rental_doc']) && $object->element != 'shipping') {
                        $this->title = 'Rental'.$this->title;
                    }

                }

                $pdf->SetSubject($outputlangs->transnoentities(empty($this->proforma) ? $this->title : "InvoiceProForma"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities(empty($this->proforma) ? $this->title : "InvoiceProForma"));
                if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                    $pdf->SetCompression(false);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                //$pdf->SetAutoPageBreak(1,0);

				// Determine category of operation
                if ($object->element == 'facture') {
                    $categoryOfOperation = 0;
                    $nbProduct = 0;
                    $nbService = 0;
                    for ($i = 0; $i < $nblines; $i++) {
                        // determine category of operation
                        if ($categoryOfOperation < 2) {
                            $lineProductType = $object->lines[$i]->product_type;
                            if ($lineProductType == Product::TYPE_PRODUCT) {
                                $nbProduct++;
                            } elseif ($lineProductType == Product::TYPE_SERVICE) {
                                $nbService++;
                            }
                            if ($nbProduct > 0 && $nbService > 0) {
                                // mixed products and services
                                $categoryOfOperation = 2;
                            }
                        }
                    }
                    // determine category of operation
                    if ($categoryOfOperation <= 0) {
                        // only services
                        if ($nbProduct == 0 && $nbService > 0) {
                            $categoryOfOperation = 1;
                        }
                    }
                    $this->categoryOfOperation = $categoryOfOperation;
                }

                // New page
                $pdf->AddPage();
                if (! empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                $this->pagenb++;

                $this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager);


                $pdf->SetFont('', '', $this->default_font_size - 1);
                $pdf->MultiCell(0, 8, '');		// Set interline to 8
                $pdf->SetTextColorArray($this->textcolor);

                if (! empty($object->array_options['options_rental_doc']) || !empty($this->nb_rental_lines)) {
                    if (getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                        $lines_head_rental = 4;
                    } else {
                        $lines_head_rental = 24;
                    }
                } else {
                    $lines_head_rental = 0;
                }

                // Affiche notes
                if (! empty($object->note_public) && empty($this->no_public_note)) {
                    $pdf->SetFont('', '', $this->default_font_size - 1);   // Dans boucle pour gerer multi-page
                    $pdf->writeHTMLCell(190, 4, 10, $this->tab_top + 1 - $lines_head_rental, dol_htmlentitiesbr($object->note_public), 0, 1);
                    $this->nexY = $pdf->GetY();
                    $height_note = $this->nexY - $this->tab_top + $lines_head_rental - 1;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);

                    $pdf->RoundedRect($this->marge_gauche, $this->tab_top - $lines_head_rental, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 2, 2, $round_corner = '1111', 'S', $this->style, $fill_color = array(''));

                    $this->tab_top = $this->nexY + (!empty($object->array_options['options_rental_doc']) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES') ? 24 : 12);
                } else {
                    $height_note = 0;
                }

                if (! empty($object->note_private) &&  ! empty($this->show_private_note)) {
                    $pdf->SetFont('', '', $this->default_font_size - 1);   // Dans boucle pour gerer multi-page
                    $pdf->writeHTMLCell(190, 4, 10, $this->tab_top + 1 - $lines_head_rental, dol_htmlentitiesbr($object->note_private), 0, 1);
                    $this->nexY = $pdf->GetY();
                    $height_note_private = $this->nexY - $this->tab_top + $lines_head_rental - 1;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192, 192, 192);

                    $pdf->RoundedRect($this->marge_gauche, $this->tab_top - $lines_head_rental, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note_private + 2, 2, $round_corner = '1111', 'S', $this->style, $fill_color = array(''));

                    $this->tab_top = $this->nexY + (!empty($object->array_options['options_rental_doc']) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES') ? 24 : 12);
                } else {
                    $height_note_private = 0;
                }

                // Show shipping date
                if (!empty($object->date_livraison) && !empty($this->show_shipping_date_above_tab)) {
                    $posy = $pdf->GetY() + 4;
                    $outputlangs->load("sendings");
                    $pdf->SetFont('', 'B', $this->default_font_size - 3);
                    $pdf->SetXY($this->marge_gauche, $posy);
                    $titre = $outputlangs->transnoentities("DateDeliveryPlanned").' :';
                    $pdf->MultiCell(80, 4, $titre, 0, 'L');
                    $pdf->SetFont('', '', $this->default_font_size - 3);
                    $pdf->SetXY(60, $posy);
                    $dlp = dol_print_date($object->date_livraison, "daytext", false, $outputlangs, true);
                    $pdf->MultiCell(65, 4, $dlp, 0, 'L');

                    $this->tab_top += 8;
                }

                $this->iniY = $this->tab_top + 10;
                $this->curY = $this->tab_top + 10;
                $this->nexY = $this->tab_top + 10;

                $this->nb_lig_tab = 0;

                $this->next_tab_top_newpage = $this->tab_top_newpage;

                if (isModEnabled('shippingcost')) {
                    $this->shipping_ht = 0;
                    $this->shipping_vat = array();
                    $this->shipping_ttc = 0;

                }

                // facture fournisseur
                if ($object->element == 'order_supplier') {
                    $this->_printlines($pdf, 0, $object, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);

                    // Show square
                    if ($this->nb_lig_tab > 0) {
                        if ($this->pagenb == 1) {
                            $this->_tableau($pdf, $object, false, $this->tab_top, $this->page_hauteur - $this->tab_top - $this->heightforavisselling - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0);
                        } else {
                            $this->_tableau($pdf, $object, false, $this->next_tab_top_newpage, $this->page_hauteur - $this->next_tab_top_newpage - $this->heightforavisselling - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0);
                        }
                    }
                } else {
                    // ecriture lignes location
                    if (isModEnabled('rental') && !empty($object->array_options['options_rental_doc']) && (getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES') || ! empty($this->nb_rental_lines))) {
                        $this->_printlines($pdf, 1, $object, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);

                        // Show square
                        if ($this->nb_lig_tab > 0) {
                            if (empty($this->nb_sell_lines) &&  !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                                $fintab = $this->page_hauteur - $this->heightforavisrental - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter;
                            } else {
                                $fintab = $this->nexY;
                            }

                            if ($this->pagenb == 1) {
                                $tabtop = $this->tab_top;
                            } else {
                                $tabtop = $this->tab_top_newpage;
                            }

                            if ($fintab <= $tabtop) {
                                $fintab = $this->page_hauteur - $this->heightforavisrental - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter;
                            }

                            $this->_tableau($pdf, $object, true, $tabtop, $fintab - $tabtop, 0, $outputlangs, 0, 0);

                            if (getDolGlobalString('ALTAPDF_SHOW_AVISRENTAL')) {
                                $this->_tableau_avis($pdf, $object, $fintab + 4, $outputlangs, true);
                            }
                        }

                        if ((!getDolGlobalString('RENTAL_NOPAGEBREAK') || $this->nexY > ($this->page_hauteur - $this->tab_top_newpage - $this->marge_haute - $this->marge_basse - $this->heightforavisrental)) && (!empty($this->nb_sell_lines) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES'))) {
                            $this->_pagefoot($pdf, $object, $outputlangs, 1);
                            $this->AddNewPage($pdf, $object, $outputlangs, $hookmanager);
                            $this->nexY = $this->tab_top_newpage + 12;
                            $this->pagenb++;
                        } else {
                            $this->tab_top = $this->nexY + $this->heightforavisrental;
                            $this->iniY = $this->tab_top + $this->heightforavisrental + 10;
                            $this->curY = $this->tab_top + $this->heightforavisrental + 10;
                            $this->nexY = $this->tab_top + $this->heightforavisrental + 10;
                            $this->next_tab_top_newpage = $this->tab_top + $this->heightforavisrental;
                        }

                        $this->nb_lig_tab = 0;
                    }

                    // ecriture lignes ventes
                    if (((!getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES') || empty($object->array_options['options_rental_doc'])) && ! empty($this->nb_sell_lines)) || $object->specimen) {
                        $this->_printlines($pdf, 0, $object, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);

                        // Show square
                        if ($this->nb_lig_tab > 0) {
                            if ($this->pagenb == 1) {
                                $this->_tableau($pdf, $object, false, $this->tab_top, $this->page_hauteur - $this->tab_top - $this->heightforavisselling - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0);
                            } else {
                                $this->_tableau($pdf, $object, false, $this->next_tab_top_newpage, $this->page_hauteur - $this->next_tab_top_newpage - $this->heightforavisselling - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0);
                            }

                            if (getDolGlobalString('ALTAPDF_SHOW_AVISADDRESS')) {
                                $this->_tableau_avis($pdf, $object, $this->page_hauteur - $this->heightforavisselling - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter + 4, $outputlangs, false);
                            }
                        }
                    }
                }

                $this->bottomlasttab = $this->page_hauteur - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter;

                // Affiche zone infos
                if (empty($this->no_tab_info)) {
                    $posy = $this->_tableau_info($pdf, $object, $this->bottomlasttab + 5, $outputlangs);
                }

                // Affiche zone totaux
                if (empty($this->no_tab_tot)) {
                    $deja_regle = $deja_regle ?? '';
                    $posy = $this->_tableau_tot($pdf, $object, $deja_regle, $this->bottomlasttab + 5, $outputlangs);

                    // Affiche zone versements
                    if ($object->element == 'facture' && ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)) {
                        $posy = $this->_tableau_versements($pdf, $object, $posy, $outputlangs);
                    }

                }

                // Pied de page
                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                // Add terms to sale
                // Affichage des CGV
                if ((int)DOL_VERSION >= 21) {
                    $mysoc = new Societe($this->db);
                    $mysoc->setMysoc($conf);
                    if (!empty($mysoc->termsofsale) && (getDolGlobalInt('MAIN_PDF_ADD_TERMSOFSALE_PROPAL') && getDolGlobalInt('ALTAPDF_ADD_TERMSOFSALE_PROPAL') && $document == 'propal') || (getDolGlobalInt('MAIN_PDF_ADD_TERMSOFSALE_ORDER') && getDolGlobalInt('ALTAPDF_ADD_TERMSOFSALE_ORDER') && $document == 'commande') || (getDolGlobalInt('MAIN_PDF_ADD_TERMSOFSALE_INVOICE') && getDolGlobalInt('ALTAPDF_ADD_TERMSOFSALE_INVOICE') && $document == 'facture')) {
                        $termsofsale = $conf->mycompany->dir_output.'/'.$mysoc->termsofsale;
                        if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                            $termsofsale = $conf->mycompany->multidir_output[$object->entity].'/'.$mysoc->termsofsale;
                        }
                        if (file_exists($termsofsale) && is_readable($termsofsale)) {
                            $pagecount = $pdf->setSourceFile($termsofsale);
                            for ($i = 1; $i <= $pagecount; $i++) {
                                $tplIdx = $pdf->importPage($i);
                                if ($tplIdx!==false) {
                                    $s = $pdf->getTemplatesize($tplIdx);
                                    $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                                    $pdf->useTemplate($tplIdx);
                                } else {
                                    setEventMessages(null, array($termsofsale.' cannot be added, probably protected PDF'), 'warnings');
                                }
                            }
                        }
                    }
                }

                $pdf->Close();

                $pdf->Output($file, 'F');


                //Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file,'object' => $object,'outputlangs' => $outputlangs);
                global $action;
                $reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

                // Permet l'enregistrement du PDF dans la table propal comme dernier PDF utilisé
                // Utile pour la signature éléctronique
                dolChmod($file);
				$this->result = array('fullpath' => $file);

                if (getDolGlobalString('MAIN_UMASK')) {
                    @chmod($file, octdec(getDolGlobalString('MAIN_UMASK')));
                }

                // suppression lignes sous-totaux location et vente
                if (!empty($object->array_options['options_rental_doc']) && $this->nb_rental_lines > 0) {
                    unset($object->lines[$this->nblines - 1]);
                    if ($this->nb_sell_lines > 0) {
                        unset($object->lines[$this->nblines - 2]);
                        if (getDolGlobalString('RENTAL_WARRANTY_MANAGEMENT')) {
                            unset($object->lines[$this->nblines - 3]);
                        }
                    } elseif (getDolGlobalString('RENTAL_WARRANTY_MANAGEMENT')) {
                        unset($object->lines[$this->nblines - 2]);
                    }
                }

                $object->fetch($object->id);
                $object->fetch_thirdparty();

                return 1;   // Pas d'erreur
            } else {
                $this->error = $outputlangs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $outputlangs->trans("ErrorConstantNotDefined", "PROP_OUTPUTDIR");
            return 0;
        }

        $this->error = $outputlangs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /**
     *  Show payments table
     *
     *  @param	PDF			&$pdf           Object PDF
     *  @param  Object		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
     */
    public function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf;

        $sign = 1;
        if ($object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
            $sign = -1;
        }

        $tab3_posx = 120;
        $tab3_top = $posy + 8;
        $tab3_width = 80;
        $tab3_height = 4;
        if ($this->page_largeur < 210) { // To work with US executive format
            $tab3_posx -= 20;
        }

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        $title = $outputlangs->transnoentities("PaymentsAlreadyDone");
        if ($object->type == 2) {
            $title = $outputlangs->transnoentities("PaymentsBackAlreadyDone");
        }

        $pdf->SetFont('', '', $default_font_size - 3);
        $pdf->SetXY($tab3_posx, $tab3_top - 4);
        $pdf->MultiCell(60, 3, $title, 0, 'L', 0);

        $pdf->line($tab3_posx, $tab3_top, $tab3_posx + $tab3_width, $tab3_top);

        $pdf->SetFont('', '', $default_font_size - 4);
        $pdf->SetXY($tab3_posx, $tab3_top);
        $pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
        $pdf->SetXY($tab3_posx + 21, $tab3_top);
        $pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
        $pdf->SetXY($tab3_posx + 40, $tab3_top);
        $pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
        $pdf->SetXY($tab3_posx + 58, $tab3_top);
        $pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

        $pdf->line($tab3_posx, $tab3_top - 1 + $tab3_height, $tab3_posx + $tab3_width, $tab3_top - 1 + $tab3_height);

        $y = 0;

        $pdf->SetFont('', '', $default_font_size - 4);

        // Loop on each deposits and credit notes included
        $sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
        $sql .= " re.description, re.fk_facture_source,";
        $sql .= " f.type, f.datef";
        $sql .= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
        $sql .= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            $invoice = new Facture($this->db);
            while ($i < $num) {
                $y += 3;
                $obj = $this->db->fetch_object($resql);

                if ($obj->type == 2) {
                    $text = $outputlangs->trans("CreditNote");
                } elseif ($obj->type == 3) {
                    $text = $outputlangs->trans("Deposit");
                } else {
                    $text = $outputlangs->trans("UnknownType");
                }

                $invoice->fetch($obj->fk_facture_source);

                $pdf->SetXY($tab3_posx, $tab3_top + $y);
                $pdf->MultiCell(20, 3, dol_print_date($obj->datef, 'day', false, $outputlangs, true), 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
                $pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
                $pdf->MultiCell(20, 3, $text, 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
                $pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

                $pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);

                $i++;
            }
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog($this->db, $this->error, LOG_ERR);
            return -1;
        }

        // Loop on each payment
        $sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,";
        $sql .= " cp.code";
        $sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
        $sql .= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
        $sql .= " ORDER BY p.datep";
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $y += 3;
                $row = $this->db->fetch_object($resql);

                $pdf->SetXY($tab3_posx, $tab3_top + $y);
                $pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date), 'day', false, $outputlangs, true), 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
                $pdf->MultiCell(20, 3, price($sign * $row->amount, 0, $outputlangs), 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
                $oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

                $pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
                $pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
                $pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

                $pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);

                $i++;
            }
        } else {
            $this->error = $this->db->lasterror();
            dol_syslog($this->db, $this->error, LOG_ERR);
            return -1;
        }

    }

    /**
     *	Show other information
     *
     *	@param      pdf             Objet PDF
     *	@param      object          Objet facture
     *	@param		posy			Position depart
     *	@param		outputlangs		Objet langs
     *	@return     y               Position pour suite
     */
    public function _tableau_avis(&$pdf, $object, $posy, $outputlangs, $rental)
    {
        global $conf;

        $pdf->SetFont('', '', $this->default_font_size - 1);

        if (empty($rental) && $object->element != 'shipping' && $object->element != 'facture') {
            // AvisAddress
            $pdf->RoundedRect($this->marge_gauche, $posy, $this->page_largeur - ($this->marge_droite + $this->marge_gauche), 8, 2, $round_corner = '1111', 'F', $this->style, $this->bgcolor);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->SetXY($this->marge_gauche, $posy + 2);
            $pdf->MultiCell($this->page_largeur - ($this->marge_droite + $this->marge_gauche), 0, $outputlangs->transnoentities("AvisAddress"), 0, 'C', 0);

        } elseif ($object->element != 'shipping' && $object->element != 'facture') {
            // AvisRental
            $pdf->RoundedRect($this->marge_gauche, $posy, $this->page_largeur - ($this->marge_droite + $this->marge_gauche), 10, 2, $round_corner = '1111', 'F', $this->style, $this->bgcolor);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->SetXY($this->marge_gauche, $posy + 1);
            $pdf->MultiCell($this->page_largeur - ($this->marge_droite + $this->marge_gauche), 0, $outputlangs->transnoentities($this->avis_rental, $this->emetteur->name), 0, 'C', 0);
            $pdf->SetXY($this->marge_gauche, $posy + 5);
            $pdf->MultiCell($this->page_largeur - ($this->marge_droite + $this->marge_gauche), 0, $outputlangs->transnoentities($this->avis_rental2), 0, 'C', 0);

            $posy += 15;

            if ($object->element != 'shipping') {
                // Remarques
                $pdf->RoundedRect($this->marge_gauche, $posy - 2, ($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 - 5, 15, 2, $round_corner = '1111', '', $this->style, $this->bgcolor);
                $pdf->SetFont('', '', $this->default_font_size - 2);
                $pdf->SetXY($this->marge_gauche, $posy);
                $pdf->MultiCell(($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 - 5, 0, $outputlangs->transnoentities("Remarks"), 0, 'C', 0);
                // Retour
                $pdf->RoundedRect(($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 + 15, $posy - 2, ($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 - 5, 15, 2, $round_corner = '1111', '', $this->style, $this->bgcolor);
                $pdf->SetFont('', '', $this->default_font_size - 2);
                $pdf->SetXY(($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 + 15, $posy);
                $pdf->MultiCell(($this->page_largeur - ($this->marge_droite + $this->marge_gauche)) / 2 - 5, 0, $outputlangs->transnoentities("Return"), 0, 'C', 0);
            }
        }

    }

    /**
     *	Show other information
     *
     *	@param      pdf             Objet PDF
     *	@param      object          Objet facture
     *	@param		posy			Position depart
     *	@param		outputlangs		Objet langs
     *	@return     y               Position pour suite
     */
    public function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf;

        $pdf->SetFont('', '', $this->default_font_size - 1);
        //$posy=$pdf->GetY()+6;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

        // If France, show VAT mention if not applicable
        if ($this->emetteur->country_code == 'FR' && $this->franchise == 1) {
            $pdf->SetFont('', 'B', $this->default_font_size - 2);
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

            $posy = $pdf->GetY() + 4;
        }

        $posxval = 42;
		$posxend = 110;

		// commented by christophe battarelif ($object->fin_validite)
        // commented by christophe battarel{
        // commented by christophe battarel$pdf->SetFont('','B', $this->default_font_size - 3);
        // commented by christophe battarel$pdf->SetXY($this->marge_gauche, $posy);
        // commented by christophe battarel$titre = $outputlangs->transnoentities("DateEndPropal").' :';
        // commented by christophe battarel$pdf->MultiCell(40, 3, $titre, 0, 'L');
        // commented by christophe battarel$pdf->SetFont('','', $this->default_font_size - 3);
        // commented by christophe battarel$pdf->SetXY($posxval, $posy);
        // commented by christophe battarel$dlp=dol_print_date($object->fin_validite,"daytext",false,$outputlangs,true);
        // commented by christophe battarel$pdf->MultiCell(80, 4, $dlp, 0, 'L');

        // commented by christophe battarel$posy=$pdf->GetY();
        // commented by christophe battarel}

        // Show shipping date
        if (!empty($object->date_livraison) && $object->element != 'propal') {
            $outputlangs->load("sendings");
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("DateDeliveryPlanned").' :';
            $pdf->MultiCell(40, 3, $titre, 0, 'L');
            $pdf->SetFont('', '', $this->default_font_size - 3);
            $pdf->SetXY($posxval, $posy);
            $dlp = dol_print_date($object->date_livraison, "daytext", false, $outputlangs, true);
            $pdf->MultiCell(80, 4, $dlp, 0, 'L');

            $posy = $pdf->GetY();
        }
        /*else*/if (!empty($object->availability_code) || !empty($object->availability)) {   // Show availability conditions
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("AvailabilityPeriod").' :';
            $pdf->MultiCell(40, 3, $titre, 0, 'L');
            $pdf->SetTextColorArray($this->textcolor);
            $pdf->SetFont('', '', $this->default_font_size - 3);
            $pdf->SetXY($posxval, $posy);
            $lib_availability = $outputlangs->transnoentities("AvailabilityType".$object->availability_code) != ('AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities("AvailabilityType".$object->availability_code) : $outputlangs->convToOutputCharset($object->availability);
            $lib_availability = str_replace('\n', "\n", $lib_availability);
            $pdf->MultiCell(80, 3, $lib_availability, 0, 'L');

            $posy = $pdf->GetY();
        }

        // Incoterm
        if (isModEnabled('incoterm')) {
            //Ajout des Incoterms dans la note public
            $resl = $this->db->query('SELECT ci.code, te.location_incoterms
					FROM '.MAIN_DB_PREFIX.'c_incoterms as ci
						LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.' as te ON (te.fk_incoterms = ci.rowid)
					WHERE te.rowid = '.$object->id);
            if ($resl) {
                $res = $this->db->fetch_object($resl);
            }

            $desc_incoterms = '';
            if ($res) {
                $desc_incoterms .= $res->code;
                if (!empty($res->location_incoterms)) {
                    $desc_incoterms .= ' - '.$res->location_incoterms;
                }
            }
            if ($desc_incoterms) {
                $pdf->SetFont('', 'B', $this->default_font_size - 3);
                $pdf->SetXY($this->marge_gauche, $posy);
                $titre = $outputlangs->transnoentities("Incoterms").' :';
                $pdf->MultiCell(40, 3, $titre, 0, 'L');
                $pdf->SetFont('', '', $this->default_font_size - 3);
                $pdf->SetXY($posxval, $posy);
                $pdf->MultiCell(80, 4, $desc_incoterms, 0, 'L');

                $posy = $pdf->GetY();

            }
        }

        // assurance prise en charge par le client
        if (!empty($this->nb_rental_lines) && empty($this->total_warranty)) {
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("RENTAL_NO_WARRANTY");
            $pdf->MultiCell(120, 4, $titre, 0, 'L');
            $posy = $pdf->GetY();
        }

        // Show payments conditions
        if (($object->element != 'propal' || !getDolGlobalString('PROPALE_PDF_HIDE_PAYMENTTERMCOND')) && ($object->cond_reglement_code || $object->cond_reglement)) {
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("PaymentConditions").' :';
            $pdf->MultiCell(40, 4, $titre, 0, 'L');

            $pdf->SetFont('', '', $this->default_font_size - 3);
            $pdf->SetXY($posxval, $posy);
            $lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
            //$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
            $pdf->MultiCell(80, 4, $lib_condition_paiement, 0, 'L');

            $posy = $pdf->GetY() + 2;
        }

        // Show category of operations
        if ($object->element == 'facture') {
            if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 2 && $this->categoryOfOperation >= 0) {
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->SetXY($this->marge_gauche, $posy);
                $categoryOfOperationTitle = $outputlangs->transnoentities("MentionCategoryOfOperations").' : ';
                $pdf->MultiCell($posxval - $this->marge_gauche, 4, $categoryOfOperationTitle, 0, 'L');

                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($posxval, $posy);
                $categoryOfOperationLabel = $outputlangs->transnoentities("MentionCategoryOfOperations" . $this->categoryOfOperation);
                $pdf->MultiCell($posxend - $posxval, 4, $categoryOfOperationLabel, 0, 'L');

                $posy = $pdf->GetY() + 3; // for 2 lines
            }
        }

        if (!getDolGlobalString('PROPALE_PDF_HIDE_PAYMENTTERMCOND')) {
            // Check a payment mode is defined
            if (empty($object->mode_reglement_code)
            && (getDolGlobalString('FACTURE_CHQ_NUMBER'))
            && !getDolGlobalString('FACTURE_RIB_NUMBER')) {
                $pdf->SetXY($this->marge_gauche, $posy);
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('helvetica', 'B', $this->default_font_size - 3);
                $pdf->MultiCell(120, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"), 0, 'L', 0);
                $pdf->SetTextColorArray($this->textcolor);

                $posy = $pdf->GetY();
            }

            // Show payment mode
            if (!empty($object->mode_reglement_code)) {
                $pdf->SetFont('', 'B', $this->default_font_size - 3);
                $pdf->SetXY($this->marge_gauche, $posy);
                $titre = $outputlangs->transnoentities("PaymentMode").':';
                $pdf->MultiCell(40, 5, $titre, 0, 'L');
                $pdf->SetFont('', '', $this->default_font_size - 3);
                $pdf->SetXY($posxval, $posy);
                $lib_mode_reg = $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
                $pdf->MultiCell(80, 5, $lib_mode_reg, 0, 'L');

                $posy = $pdf->GetY();
            }

            if ($object->element != 'order_supplier') {
                // Show payment mode CHQ
                if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
                    // Si mode reglement non force ou si force a CHQ
                    if (getDolGlobalString('FACTURE_CHQ_NUMBER')) {
                        if (getDolGlobalString('FACTURE_CHQ_NUMBER') > 0) {
                            $account = new Account($this->db);
                            $account->fetch(getDolGlobalString('FACTURE_CHQ_NUMBER'));

                            $pdf->SetXY($this->marge_gauche, $posy);
                            $pdf->SetFont('', 'B', $this->default_font_size - 3);
                            $pdf->MultiCell(120, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio).':', 0, 'L', 0);
                            $posy = $pdf->GetY();

                            if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
                                $pdf->SetXY($this->marge_gauche, $posy);
                                $pdf->SetFont('', '', $this->default_font_size - 2);
                                $pdf->MultiCell(120, 3, $outputlangs->convToOutputCharset($account->adresse_proprio), 0, 'L', 0);
                                $posy = $pdf->GetY();
                            }
                        }
                        if (getDolGlobalString('FACTURE_CHQ_NUMBER') == -1) {
                            $pdf->SetXY($this->marge_gauche, $posy);
                            $pdf->SetFont('', 'B', $this->default_font_size - 2);
                            $pdf->MultiCell(120, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$outputlangs->convToOutputCharset($this->emetteur->name).' '.$outputlangs->transnoentities('SendTo').':', 0, 'L', 0);
                            $posy = $pdf->GetY();

                            if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
                                $pdf->SetXY($this->marge_gauche, $posy);
                                $pdf->SetFont('', '', $this->default_font_size - 2);
                                $pdf->MultiCell(120, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
                                $posy = $pdf->GetY();
                            }
                        }
                    }
                }

                // If payment mode not forced or forced to VIR, show payment with BAN
                if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
                    if (getDolGlobalString('FACTURE_RIB_NUMBER')) {
                        if (!empty($object->fk_account)) {
                            $bankid = ($object->fk_account <= 0 ? getDolGlobalString('FACTURE_RIB_NUMBER') : $object->fk_account);
                            if ($object->fk_bank > 0) {
                                $bankid = $object->fk_bank; // For backward compatibility when object->fk_account is forced with object->fk_bank
                            }
                        }

                        $account = new Account($this->db);
                        if (!empty($bankid)) {
                            $account->fetch($bankid);
                        } else {
                            $account->fetch(getDolGlobalString('FACTURE_RIB_NUMBER'));
                        }

                        $curx = $this->marge_gauche;
                        $this->curY = $posy;

                        if (!getDolGlobalString('PDF_SHORT_IBAN_BIC')) {
                            $posy = pdf_bank($pdf, $outputlangs, $curx, $this->curY, $account, 0, $this->default_font_size);
                        } else {
                            // Use correct name of bank id according to country
                            $bickey = "BICNumber";
                            if ($account->getCountryCode() == 'IN') {
                                $bickey = "SWIFT";
                            }

                            // Use correct name of bank id according to country
                            require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';
                            $ibankey = FormBank::getIBANLabel($account);

                            if (! empty($account->iban)) {
                                //Remove whitespaces to ensure we are dealing with the format we expect
                                $ibanDisplay_temp = str_replace(' ', '', $outputlangs->convToOutputCharset($account->iban));
                                $ibanDisplay = "";

                                $nbIbanDisplay_temp = dol_strlen($ibanDisplay_temp);
                                for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
                                    $ibanDisplay .= $ibanDisplay_temp[$i];
                                    if ($i % 4 == 3 && $i > 0) {
                                        $ibanDisplay .= " ";
                                    }
                                }

                                $pdf->SetFont('', 'B', $this->default_font_size - 3);
                                $pdf->SetXY($curx, $posy);
                                $pdf->MultiCell(120, 3, $outputlangs->transnoentities($ibankey).': ' . $ibanDisplay, 0, 'L', 0);
                                $posy += 3;
                            }

                            if (! empty($account->bic)) {
                                $pdf->SetFont('', 'B', $this->default_font_size - 3);
                                $pdf->SetXY($curx, $posy);
                                $pdf->MultiCell(120, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
                            }
                        }

                        $posy += 2;
                    }
                }
            }
        }

        // Mentions légales - Exonération TVA
        if (getDolGlobalInt('ALTAPDF_EXONERATION_TVA_LEGAL_NOTICE')) {
            $sql = "SELECT rowid, code, label, eec";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_country";
            $sql .= " WHERE code = '".$object->thirdparty->country_code."'";
            $resql=$this->db->query($sql);
            if (!empty($resql)) {
                $num=$this->db->num_rows($resql);
                if(!empty($num)) {
                    $europe = $object->db->fetch_object($resql);
                }
            }
            $object->db->free($resql);
            if ($europe->code != 'FR') {
                $pdf->SetFont('', '', $this->default_font_size - 1);
                // Hors france
                if (!empty($europe->eec)) {
                    if ($object->mode_reglement_code == "VIR") {
                        $pdf->SetXY($curx, $posy);
                        $pdf->MultiCell(120, 5, $outputlangs->transnoentities('ExoTvaIntra'), 0, 'L', 0);
                    } elseif($object->mode_reglement_code == 'CHQ') {
                        $pdf->SetXY($curx + 10, $posy + 5);
                        $pdf->MultiCell(120, 5, $outputlangs->transnoentities('ExoTvaIntra'), 0, 'L', 0);
                    }elseif($object->mode_reglement_code == 'CB' || $object->mode_reglement_code == 'LIQ' || $object->mode_reglement_code == 'PRE' || $object->mode_reglement_code == 'P4') {
                        $pdf->MultiCell(120, 5, $outputlangs->transnoentities('ExoTvaIntra'), 0, 'L', 0);
                    }
                } else {
                    $pdf->MultiCell(120, 5, $outputlangs->transnoentities('ExoTvaHorsUe'), 0, 'L', 0);
                }
            }
        }

        // numéros d'urgence location
        if (!empty($this->nb_rental_lines) && ($object->element == 'commande' || $object->element == 'propal') && getDolGlobalString('EMERGENCY_PHONE_NUMBER')) {
            $pdf->SetFont('', 'B', $this->default_font_size - 3);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("Emergency").' :';
            $pdf->MultiCell(40, 3, $titre, 0, 'L');
            $pdf->SetFont('', '', $this->default_font_size - 3);
            $pdf->SetXY($posxval, $posy);
            $pdf->writeHTMLCell(80, 4, $posxval, $posy, getDolGlobalString('EMERGENCY_PHONE_NUMBER'), 0, 1);
            $posy = $pdf->GetY() + 2;
        }

        // confirmation date livraison
        // commented by christophe battarelif (empty($object->array_options['options_rental_doc']) && $object->element == 'commande' && !empty($dlp))
        // commented by christophe battarel{
        // commented by christophe battarel$pdf->SetFont('','B', $this->default_font_size - 3);
        // commented by christophe battarel$pdf->SetXY($this->marge_gauche, $posy);
        // commented by christophe battarel$titre = $outputlangs->transnoentities("DELIVERY_CONFIRM", $dlp);
        // commented by christophe battarel$pdf->MultiCell(120, 4, $titre, 0, 'L');
        // commented by christophe battarel$posy=$pdf->GetY()+2;
        // commented by christophe battarel}

        // cadre bon pour accord
        if ((!empty($object->array_options['options_rental_doc']) && $object->element == 'commande' && !getDolGlobalString('PDF_HIDE_AGREEMENT_ON_RENTAL_ORDER')) || ($object->element == 'propal' && !getDolGlobalString('PDF_HIDE_AGREEMENT_ON_PROPAL')) || ($object->element == 'shipping' && !getDolGlobalString('PDF_HIDE_AGREEMENT_ON_SHIPPING'))) {
            $this->curY = 252 - $this->heightforfreetext;
            if (getDolGlobalString('PROPOSAL_ALLOW_ONLINESIGN') && $object->element == 'propal') {
                $this->curY = 232 - $this->heightforfreetext;
            }
            if (!getDolGlobalString('PROPOSAL_ALLOW_ONLINESIGN') && $object->element == 'propal') {
                $this->curY = 247 - $this->heightforfreetext;
            }
            $deltax = $this->marge_gauche + 100;
            $pdf->RoundedRect($deltax, $this->curY, 92, 30, 2, $round_corner = '1111', 'S', $this->style, '');
            $pdf->SetFont('', 'B', $this->default_font_size - 1);
            $pdf->SetXY($deltax, $this->curY);
            $titre = $outputlangs->transnoentities('DocORDER1');
            $pdf->MultiCell(80, 5, $titre, 0, 'L', 0);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->SetXY($deltax, $this->curY + 5);
            $pdf->SetFont('', 'I', $this->default_font_size - 2);

            $pdf->MultiCell(90, 3, $outputlangs->transnoentities('DocORDER2'), 0, 'L', 0);
            $pdf->SetXY($deltax, $this->curY + 12);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER3'), 0, 'L', 0);
        } elseif ($object->element == 'facture' && !empty($object->array_options['options_shop'])) {
            $this->curY = 252 - $this->heightforfreetext;
            if (getDolGlobalString('PROPOSAL_ALLOW_ONLINESIGN') && $object->element == 'propal') {
                $this->curY = 232 - $this->heightforfreetext;
            }
            $deltax = $this->marge_gauche + 108;
            $pdf->RoundedRect($deltax, $this->curY, 85, 30, 2, $round_corner = '1111', 'S', $this->style, '');
            $pdf->SetFont('', 'B', $this->default_font_size - 1);
            $pdf->SetXY($deltax, $this->curY);
            $titre = $outputlangs->transnoentities('DocORDERSHOP');
            $pdf->MultiCell(80, 5, $titre, 0, 'L', 0);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->SetXY($deltax, $this->curY + 5);
            $pdf->SetFont('', 'I', $this->default_font_size - 2);
            $pdf->MultiCell(90, 3, $outputlangs->transnoentities('DocORDER2'), 0, 'L', 0);
            $pdf->SetXY($deltax, $this->curY + 12);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER3'), 0, 'L', 0);
        }




        // cadre bon pour accord 2
        // if ((!empty($object->array_options['options_rental_doc']) || ($object->element == 'propal' && !getDolGlobalString('PDF_HIDE_AGREEMENT_ON_PROPAL')))) {

        if (!empty($this->two_signatures)) {
            $this->curY = 252 - $this->heightforfreetext;
            if (getDolGlobalString('PROPOSAL_ALLOW_ONLINESIGN') && $object->element == 'propal') {
                $this->curY = 232 - $this->heightforfreetext;
            }
            $deltax = $this->marge_gauche - 2;
            $pdf->RoundedRect($deltax, $this->curY, 92, 30, 2, $round_corner = '1111', 'S', $this->style, '');
            $pdf->SetFont('', 'B', $this->default_font_size - 1);
            $pdf->SetXY($deltax, $this->curY);
            $titre = $outputlangs->transnoentities('Cadre1');
            $pdf->MultiCell(80, 5, $titre, 0, 'L', 0);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->SetXY($deltax, $this->curY + 5);
            $pdf->SetFont('', 'I', $this->default_font_size - 2);
            $pdf->MultiCell(88, 3, $outputlangs->transnoentities('Cadre2'), 0, 'L', 0);
            $pdf->SetXY($deltax, $this->curY + 18);
            $pdf->SetFont('', '', $this->default_font_size - 2);
            $pdf->MultiCell(80, 3, $outputlangs->transnoentities('Cadre3'), 0, 'L', 0);
        }

        return $posy;
    }

    /**
     *	Show total to pay
     *
     *	@param	PDF			$pdf           Object PDF
     *	@param  Facture		$object         Object invoice
     *	@param  int			$deja_regle     Montant deja regle
     *	@param	int			$posy			Position depart
     *	@param	Translate	$outputlangs	Objet langs
     *	@return int							Position pour suite
     */
    public function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
    {
        global $conf,$mysoc;

        if ($object->element == 'shipping' || $object->element == 'return_productReturn' || $object->element == 'productreturn') {
            return $posy;
        }

        // Open DSI -- LV -- Multicurrency -- Begin
        //$currency_code = $this->currency_code;
        // Open DSI -- LV -- Multicurrency -- End

        $sign = 1;
        if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
            $sign = -1;
        }

        $tab2_top = $posy;
        $tab2_hl = 4;
        $pdf->SetFont('', '', $this->default_font_size - 1);

        // Tableau total
        $posx = 120;
        $lltot = 200;
        $col1x = $posx + 2;
        $col2x = 165;
        if ($this->page_largeur < 210) { // To work with US executive format
            $col2x -= 20;
        }
        $largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);
        $posxrec = $posx;
        $widthrec = $this->page_largeur - ($this->marge_droite + $posxrec);

        // Open DSI -- LV -- Multicurrency -- Begin
        // calculate amounts
        $total_ht  = (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
        $total_ttc = (isModEnabled('multicurrency') && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
        // Open DSI -- LV -- Multicurrency -- End

        $index = -1;
        $useborder = 0;

        $pdf->SetFillColor(255, 255, 255);

        // totaux location
        if (isModEnabled('rental') && !empty($this->total_rental)) {
            //MONTANT LOCATION HT (Equipement)
            $index++;
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RentalTotalHT"), 0, 'L', 1);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            // Open DSI -- LV -- Multicurrency -- Begin
            //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_rental_cur, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
            $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_rental_cur, 0, $outputlangs), 0, 'R', 1);
            // Open DSI -- LV -- Multicurrency -- End

            //MONTANT ASSURANCE HT
            if (getDolGlobalString('PRINT_WARRANTY_TOTAL')) {
                if (!empty($this->total_warranty)) {
                    $index++;
                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RentalWarrantyTotalHT"), 0, 'L', 1);
                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    // Open DSI -- LV -- Multicurrency -- Begin
                    //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_warranty_cur, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_warranty_cur, 0, $outputlangs), 0, 'R', 1);
                    // Open DSI -- LV -- Multicurrency -- End
                }
            }

            //MONTANT VENTE HT
            if (!empty($this->total_selling)) {
                $index++;
                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("SellingTotalHT"), 0, 'L', 1);
                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                // Open DSI -- LV -- Multicurrency -- Begin
                //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_selling_cur, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
                $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->total_selling_cur, 0, $outputlangs), 0, 'R', 1);
                // Open DSI -- LV -- Multicurrency -- End
            }
        }

        // Shipping HT
        if (isModEnabled('shippingcost') && getDolGlobalString('PRINT_SHIPPING_HT') && ($this->shipping_ht != 0 || getDolGlobalString('PRINT_SHIPPING_NULL'))) {
            $index++;
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHTNet"), 0, 'L', 1);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            // Open DSI -- LV -- Multicurrency -- Begin
            //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->shipping_ht, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
            $pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($total_ht + (! empty($object->remise) ? $object->remise : 0) - $this->shipping_ht), 0, $outputlangs), 0, 'R', 1);
            // Open DSI -- LV -- Multicurrency -- End

            $index++;
            $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ShippingHT"), 0, 'L', 1);
            $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
            // Open DSI -- LV -- Multicurrency -- Begin
            //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->shipping_ht, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
            $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->shipping_ht, 0, $outputlangs), 0, 'R', 1);
            // Open DSI -- LV -- Multicurrency -- End
        }

        // Total HT
        $index++;
        $pdf->SetFont('', 'B', $this->default_font_size);
        $pdf->SetFillColor(248, 248, 248);

        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
        // Open DSI -- LV -- Multicurrency -- Begin
        //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->total_ht + (! empty($object->remise)?$object->remise:0)), 0, $outputlangs).''.$currency_code, 0, 'R', 1);
        $pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($total_ht + (! empty($object->remise) ? $object->remise : 0)), 0, $outputlangs), 0, 'R', 1);
        // Open DSI -- LV -- Multicurrency -- End
        $pdf->SetFont('', '', $this->default_font_size);


        // Show VAT by rates and total
        $this->atleastoneratenotnull = 0;
        if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
            $tvaisnull = ((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
            if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_ISNULL') && $tvaisnull) {
                // Nothing to do
            } else {
                foreach ($this->tva as $tvakey => $tvaval) {
                    if ($tvakey > 0) {    // On affiche pas taux 0
                        $this->atleastoneratenotnull++;

                        $index++;
                        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

                        $tvacompl = '';
                        if (preg_match('/\*/', $tvakey)) {
                            $tvakey = str_replace('*', '', $tvakey);
                            $tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
                        }
                        $totalvat = $outputlangs->transnoentities("TotalVAT").' ';
                        $totalvat .= vatrate($tvakey, 1).$tvacompl;
                        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                        // Open DSI -- LV -- Multicurrency -- Begin
                        //$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
                        $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
                        // Open DSI -- LV -- Multicurrency -- End
                    }
                }

                if (! $this->atleastoneratenotnull) { // If not vat at all
                    $index++;
                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);

                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    // Open DSI -- LV -- Multicurrency -- Begin
                    //$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva).''.$currency_code, 0, 'R', 1);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva), 0, 'R', 1);
                    // Open DSI -- LV -- Multicurrency -- End

                    // Total LocalTax1
                    if (getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') == 'localtax1on' && $object->total_localtax1 > 0) {
                        $index++;
                        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT1".$mysoc->country_code), $useborder, 'L', 1);
                        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                        // Open DSI -- LV -- Multicurrency -- Begin
                        //$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax1, 0, $outputlangs).''.$currency_code, $useborder, 'R', 1);
                        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax1, 0, $outputlangs), $useborder, 'R', 1);
                        // Open DSI -- LV -- Multicurrency -- End
                    }

                    // Total LocalTax2
                    if (getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION') == 'localtax2on' && $object->total_localtax2 > 0) {
                        $index++;
                        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT2".$mysoc->country_code), $useborder, 'L', 1);
                        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                        // Open DSI -- LV -- Multicurrency -- Begin
                        //$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax2, 0, $outputlangs).''.$currency_code, $useborder, 'R', 1);
                        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax2, 0, $outputlangs), $useborder, 'R', 1);
                        // Open DSI -- LV -- Multicurrency -- End
                    }
                } else {
                    //Local tax 1
                    if (getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') == 'localtax1on') {
                        foreach ($this->localtax1 as $tvakey => $tvaval) {
                            if ($tvakey > 0) {    // On affiche pas taux 0
                                //$this->atleastoneratenotnull++;

                                $index++;
                                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

                                $tvacompl = '';
                                if (preg_match('/\*/', $tvakey)) {
                                    $tvakey = str_replace('*', '', $tvakey);
                                    $tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
                                }
                                $totalvat = $outputlangs->transnoentities("TotalLT1".$mysoc->country_code).' ';
                                $totalvat .= vatrate($tvakey, 1).$tvacompl;
                                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                                // Open DSI -- LV -- Multicurrency -- Begin
                                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
                                // Open DSI -- LV -- Multicurrency -- End
                            }
                        }
                    }

                    //Local tax 2
                    if (getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION') == 'localtax2on') {
                        foreach ($this->localtax2 as $tvakey => $tvaval) {
                            if ($tvakey > 0) {    // On affiche pas taux 0
                                //$this->atleastoneratenotnull++;

                                $index++;
                                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

                                $tvacompl = '';
                                if (preg_match('/\*/', $tvakey)) {
                                    $tvakey = str_replace('*', '', $tvakey);
                                    $tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
                                }
                                $totalvat = $outputlangs->transnoentities("TotalLT2".$mysoc->country_code).' ';
                                $totalvat .= vatrate($tvakey, 1).$tvacompl;
                                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                                // Open DSI -- LV -- Multicurrency -- Begin
                                //$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs).''.$currency_code, 0, 'R', 1);
                                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
                                // Open DSI -- LV -- Multicurrency -- End

                            }
                        }
                    }
                }

                // Revenue stamp
                if (!empty($object->revenuestamp) && (float) price2num($object->revenuestamp) != 0) {
                    $index++;
                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    // Open DSI -- LV -- Multicurrency -- Begin
                    //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp, 0, $outputlangs).''.$currency_code, $useborder, 'R', 1);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp, 0, $outputlangs), $useborder, 'R', 1);
                    // Open DSI -- LV -- Multicurrency -- End
                }

                // Shipping VAT
                if (isModEnabled('shippingcost') && getDolGlobalString('PRINT_SHIPPING_VAT') && count($this->shipping_vat) > 0) {
                    ksort($this->shipping_vat);
                    foreach ($this->shipping_vat as $vat_rate => $vat_amount) {
                        $index++;
                        $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                        $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ShippingVAT").' '.vatrate($vat_rate, 1), 0, 'L', 1);
                        $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                        $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $vat_amount, 0, $outputlangs), 0, 'R', 1);
                    }
                }

                // Shipping TTC
                if (isModEnabled('shippingcost') && getDolGlobalString('PRINT_SHIPPING_TTC') && ($this->shipping_ttc != 0 || getDolGlobalString('PRINT_SHIPPING_NULL'))) {
                    $index++;
                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ShippingTTC"), 0, 'L', 1);
                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $this->shipping_ttc, 0, $outputlangs), 0, 'R', 1);
                }

                // Total TTC
                $index++;
                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                $pdf->SetTextColorArray($this->textcolor);
                $pdf->SetFillColorArray($this->bgcolor);
                $pdf->SetFont('', 'B', $this->default_font_size);
                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                // Open DSI -- LV -- Multicurrency -- Begin
                //$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_ttc, 0, $outputlangs).''.$currency_code, $useborder, 'R', 1);
                $pdf->MultiCell($largcol2, $tab2_hl, price($sign * $total_ttc, 0, $outputlangs), $useborder, 'R', 1);
                // Open DSI -- LV -- Multicurrency -- End
                $pdf->SetTextColorArray($this->textcolor);
            }
        }

        if ($object->element == 'facture') {
            // $test = new Facture()
            // regarder dans le fetch des facture et facture fourn multicurrency_tx
            // vérifier si pas de warning
            $creditnoteamount = $object->getSumCreditNotesUsed((isModEnabled('multicurrency') && $object->multicurrency_tx != 1) ? 1 : 0);	// Warning, this also include excess received
            $depositsamount = $object->getSumDepositsUsed((isModEnabled('multicurrency') && $object->multicurrency_tx != 1) ? 1 : 0);
            //print "x".$creditnoteamount."-".$depositsamount;exit;
            $resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
            if ($object->paye) {
                $resteapayer = 0;
            }

            if (($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) && !getDolGlobalString('INVOICE_NO_PAYMENT_DETAILS')) {
                // Already paid + Deposits
                $index++;
                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);

                // Credit note
                if ($creditnoteamount) {
                    $labeltouse = ($outputlangs->transnoentities("CreditNotesOrExcessReceived") != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities("CreditNotesOrExcessReceived") : $outputlangs->transnoentities("CreditNotes");
                    $index++;
                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $labeltouse, 0, 'L', 0);
                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
                }

                // Escompte
                if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
                    $index++;
                    $pdf->SetFillColor(255, 255, 255);

                    $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
                    $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                    $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

                    $resteapayer = 0;
                }

                $index++;
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFillColor(224, 224, 224);
                $pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
                $pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

                $pdf->SetFont('', '', $default_font_size ?? 0 - 1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

        $index++;
        $posy = $tab2_top + ($tab2_hl * $index);

        if ($object->element == 'commande' && empty($object->array_options['options_rental_doc']) && getDolGlobalString('ALTAPDF_SHOW_TOT_WARNING')) {
            $pdf->SetXY($posxrec, $posy + 5);
            $pdf->SetFont('', 'I', $this->default_font_size - 3);
            $pdf->MultiCell($widthrec, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);
            $posy = $pdf->getY();
            $pdf->RoundedRect($posx, $posy, 0, 0, 2, $round_corner = '1111', 'F', '', $this->bgcolor);
        }

        // Fin
        $pdf->SetFont('', '', $this->default_font_size - 1);
        $pdf->SetTextColorArray($this->textcolor);

        return $posy;
    }


    /**
     *  Show top header of page.
     *
     *  @param	PDF			$pdf     		Object PDF
     *  @param  Object		$object     	Object to show
     *  @param  int	    	$showaddress    0=no, 1=yes
     *  @param  Translate	$outputlangs	Object lang for output
     *  @param	object		$hookmanager	Hookmanager object
     *  @return	void
     */
    public function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $hookmanager)
    {
        global $conf,$langs;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $width_logo = 60;
        $posy = $this->marge_haute;
        $posx = $this->marge_gauche + $width_logo;
        $width_right_header = $this->page_largeur - $posx - 10;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo = !empty($this->logo) ? $this->logo : $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        if ($logo || $this->emetteur->logo) {
            if (is_readable($logo)) {
                // commented by christophe battarel$pdf->Image($logo, $this->marge_gauche, $posy, $width_logo, 0);	// height=0 (auto)
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell($width_logo, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell($width_logo, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell($width_logo, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $logo_height = max(33, $pdf->getY());


        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = $outputlangs->transnoentities(empty($this->proforma) ? $this->title : "InvoiceProForma");

        $pdf->MultiCell($width_right_header, 4, $title, '', 'R');

        $pdf->SetFont('', 'B', $default_font_size);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell($width_right_header, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

        $posy += 2;
        $pdf->SetFont('', '', $default_font_size - 1);

        if ($object->element == 'order_supplier' && $object->ref_supplier) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("RefSupplier")." : " . $outputlangs->convToOutputCharset($object->ref_supplier), '', 'R');
        } elseif (!empty($object->ref_client)) {
            $posy += 5;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset(trim($object->ref_client)), '', 'R');
        }

        if ($object->element == 'propal') {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date, "day", false, $outputlangs, true), '', 'R');

            if (!getDolGlobalString('PDF_PROPAL_HIDE_DATEEND')) {
                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("DateEndPropal")." : " . dol_print_date($object->fin_validite, "day", false, $outputlangs, true), '', 'R');
            }
        } elseif ($object->element == 'commande') {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date, "%d %b %Y", false, $outputlangs, true), '', 'R');
        } elseif ($object->element == 'shipping') {
            $classname = ucfirst($object->origin);
            $objectsrc = new $classname($this->db);
            $objectsrc->fetch($object->origin_id);

            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColorArray($this->textcolor);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("Order")." : " . $objectsrc->ref, '', 'R');

            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_creation, "%d %b %Y", false, $outputlangs, true), '', 'R');
        } elseif ($object->element == 'facture') {
            $objectidnext = $object->getIdReplacingInvoice('validated');
            if ($object->type == 0 && $objectidnext) {
                $objectreplacing = new Facture($this->db);
                $objectreplacing->fetch($objectidnext);

                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
            }
            if ($object->type == 1) {
                $objectreplaced = new Facture($this->db);
                $objectreplaced->fetch($object->fk_facture_source);

                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
            }
            if ($object->type == 2) {
                $objectreplaced = new Facture($this->db);
                $objectreplaced->fetch($object->fk_facture_source);

                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
            }

            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("DateInvoice")." : " . dol_print_date($object->date, "day", false, $outputlangs), '', 'R');

            if ($object->type != 2) {
                $posy += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("DateDue")." : " . dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true), '', 'R');
            }

        } elseif ($object->element == 'order_supplier') {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            if (! empty($object->date_commande)) {
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date_commande, "day", false, $outputlangs, true), '', 'R');
            } else {
                $pdf->SetTextColor(255, 0, 0);
                $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("OrderToProcess"), '', 'R');
            }
        }

        if ($object->element != 'order_supplier' && $object->thirdparty->code_client) {
            $posy += 4;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($width_right_header, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        $posy += 1;

        // Show list of linked objects
        $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $width_right_header, 3, 'R', $default_font_size + 1);

        $this->tab_top_newpage = $pdf->getY() + (empty($this->nb_rental_lines) ? 15 : 30);

        $nexY = max($logo_height, $pdf->getY()) + 8;

        if ($showaddress) {
            // Sender properties
            $carac_emetteur = '';
            // Add internal contact of proposal if defined
            if (getDolGlobalString('PDF_HIDE_SALES_REP_IN_SENDER_FRAME')) {
                $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
                if (count($arrayidcontact) > 0) {
                    $object->fetch_user($arrayidcontact[0]);
                    $carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
                }
            }

            $conf->global->MAIN_PDF_DISABLESOURCEDETAILS = (isset($this->header_address_blocks['sender']['details']) && $this->header_address_blocks['sender']['details'] == 'N');
            $carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur);

            if (isset($this->header_address_blocks['sender'])) {
                // Show sender
                $posy = $nexY;
                $posx = $this->marge_gauche;
                if (isset($this->header_address_blocks['sender']['pos'])) {
                    $posx = $this->header_address_blocks['sender']['pos'];
                }
                if (isset($this->header_address_blocks['sender']['posy'])) {
                    $posy = $this->header_address_blocks['sender']['posy'];
                }
                $hautcadre = 44;
                $widthrecbox = 92;
                if (isset($this->header_address_blocks['sender']['width'])) {
                    $widthrecbox = $this->header_address_blocks['sender']['width'];
                }
                if (isset($this->header_address_blocks['sender']['height'])) {
                    $hautcadre = $this->header_address_blocks['sender']['height'];
                }
                if (isset($this->header_address_blocks['sender']['bgcolor'])) {
                    $bgcolor = explode(',', $this->header_address_blocks['sender']['bgcolor']);
                } else {
                    $bgcolor = $this->bgcolor;
                }
                if (isset($this->header_address_blocks['sender']['header_align'])) {
                    $header_align = $this->header_address_blocks['sender']['header_align'];
                } else {
                    $header_align = 'L';
                }
                if (isset($this->header_address_blocks['sender']['field_align'])) {
                    $field_align = $this->header_address_blocks['sender']['field_align'];
                } else {
                    $field_align = 'L';
                }
                if (isset($this->header_address_blocks['sender']['padding_left'])) {
                    $padding_left = $this->header_address_blocks['sender']['padding_left'];
                } else {
                    $padding_left = 2;
                }

                // Show sender frame
                if (empty($this->header_address_blocks['sender']['noframe'])) {
                    $pdf->SetTextColorArray($this->textcolor);
                    $pdf->SetFont('', '', $this->default_font_size - 1);
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom"), 0, $header_align);
                    $pdf->SetXY($posx, $posy);
                    $pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);
                }

                // Show sender name center
                $pdf->SetXY($posx + $padding_left, $posy + 3);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->MultiCell($widthrecbox, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $field_align);
                $posy = $pdf->getY();

                // Show sender information
                $pdf->SetXY($posx + $padding_left, $posy);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell($widthrecbox, 4, $carac_emetteur, 0, $field_align);
            }

            if ($object->element != 'shipping') {
                $obj = $object;
            } else {
                $obj = $objectsrc;
                $obj->fetch_thirdparty();
            }

            // If CUSTOMER contact defined, we use it
            $usecontact = false;
            if (!getDolGlobalString('PDF_HIDE_CONTACT_IN_CUSTOMER_FRAME')) {
                $arrayidcontact = array_merge($obj->getIdContact('external', 'CUSTOMER'), $obj->getIdContact('external', 'BILLING'));
                if (count($arrayidcontact) > 0) {
                    $usecontact = true;
                    $result = $obj->fetch_contact($arrayidcontact[0]);
                }
            }

            if ($usecontact && getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
                $thirdparty = $object->contact;
            } else {
                $thirdparty = $object->thirdparty;
            }

            $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

            if (getDolGlobalString('ALTAPDF_FORCE_RECIPIENT_COUNTRY')) {
                $this->emetteur->pays_code = "";
            } //pour vider le champ "pays" afin de pouvoir ensuite l'afficher dans l'adresse (à cause d'un test par défaut "si pays différent de celui de l'émeteur, on le met pas")

            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $obj->contact : ''), $usecontact, 'target', $object);

            if (getDolGlobalString('ALTAPDF_TVA_IN_RECIPIENT_ADDRESS_BOTTOM') && !empty($object->thirdparty->tva_intra)) {
                $carac_client .= "\n\n\n\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($object->thirdparty->tva_intra);
            }


            // chercher shipping_site
            $shipping_site = $obj->array_options['options_shipping_site'] ?? '';
            $manual_shipping_site = $obj->array_options['options_manual_shipping_site'] ?? '';
            if (!empty($manual_shipping_site)) {
                $carac_shipping = $manual_shipping_site;
            } elseif (!empty($shipping_site) && $shipping_site != 'Client') {
                $carac_shipping = $outputlangs->transnoentities($shipping_site);
            } elseif ($object->element == 'order_supplier') {
                $conf->global->MAIN_PDF_DISABLESOURCEDETAILS = true;
                $carac_shipping = pdf_build_address($outputlangs, $this->emetteur);
            } elseif ($this->name != 'customerboard' && $this->name != 'supplierboard') {
                // look if shipping address on thirdparty
                $obj->thirdparty->fetch_optionals($obj->thirdparty->id);
                if (!empty($obj->thirdparty->array_options['options_shipping_address'])) {
                    $carac_shipping = $obj->thirdparty->array_options['options_shipping_address'];
                } else {
                    // Option altapdf (et core) ne pas afficher le SIRET et SIREN dans le bloc adresse de livraison (devis, commandes client, factures)
                    if (getDolGlobalInt('ALTAPDF_HIDE_PROFID1_PROFID2_SHIPPING_ADDRESS') && (getDolGlobalInt('MAIN_PROFID1_IN_ADDRESS') || getDolGlobalInt('MAIN_PROFID2_IN_ADDRESS'))) {
                        $conf->global->MAIN_PROFID1_IN_ADDRESS = 0; // SIREN
                        $conf->global->MAIN_PROFID2_IN_ADDRESS = 0; // SIRET
                    }

                    // look if a shipping contact exist
                    $contact_id = $obj->getIdContact('external', 'SHIPPING');
                    if (!empty($contact_id)) {
                        $obj->fetch_contact($contact_id[0]);// adresse de livraison3
                        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                        $shipping_contact  = new Societe($this->db);
                        if ($shipping_contact->fetch($obj->contact->socid) <= 0) {
                            $shipping_contact = $object->thirdparty;
                        }
                        $carac_shipping = pdf_build_address($outputlangs, $this->emetteur, $shipping_contact, $obj->contact, true, 'targetwithdetails_phone');
                    } else {
                        $carac_shipping = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, $obj->contact, false, 'targetwithdetails_phone');
                    }
                }
            } else {
                $carac_shipping = pdf_build_address($outputlangs, $this->emetteur, $object, $obj->contact, false, 'targetwithdetails_phone');
            }

            if ($object->element == 'order_supplier') {
                $carac_shipping = '';

                // Afficher l'adresse de livraison manuelle
                if (!empty($object->array_options['options_manual_shipping_site'])) {
                    $carac_shipping = $object->array_options['options_manual_shipping_site'];
                }
                // Afficher le site de livraison
                elseif (!empty($object->array_options['options_shipping_site_supplier_order'])) {
                    require_once DOL_DOCUMENT_ROOT . "/core/class/extrafields.class.php";
                    $extra_supplier_order = new ExtraFields($this->db);
                    $extra_supplier_order->fetch_name_optionals_label('commande_fournisseur');
                    $shipping = $extra_supplier_order->attributes["commande_fournisseur"]["param"]["shipping_site_supplier_order"]["options"];

                    foreach ($shipping as $key => $value) {
                        if ($key == $object->array_options['options_shipping_site_supplier_order'] ) {

                            // On vérifie les correspondances avec le nom dans Société et entrepôt
                            require_once DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";
                            $societe = new Societe($this->db);
                            $adresse_societe = $societe->fetch('', $value);

                            require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
                            $entrepot = new Entrepot($this->db);
                            $adresse_entrepot = $entrepot->fetch('', $value);

                            // On affiche l'adresse qui correspond à notre correspondance, s'il y a deux correspondances, prendre la société par défaut
                            if (!empty($adresse_societe) && empty($adresse_entrepot)) {
                                $carac_shipping = $societe->name.'<br>';
                                $carac_shipping .= $societe->address;
                                $carac_shipping .= '<br>'.$societe->zip;
                                $carac_shipping .= ' '.$societe->town;
                                $carac_shipping .= ' '.$societe->country;
                            } elseif (!empty($adresse_societe) && !empty($adresse_entrepot)) {
                                $carac_shipping = $societe->name.'<br>';
                                $carac_shipping .= $societe->address;
                                $carac_shipping .= '<br>'.$societe->zip;
                                $carac_shipping .= ' '.$societe->town;
                                $carac_shipping .= ' '.$societe->country;
                            } else {
                                $carac_shipping = $entrepot->label.'<br>';
                                $carac_shipping .= $entrepot->address;
                                $carac_shipping .= '<br>'.$entrepot->zip;
                                $carac_shipping .= ' '.$entrepot->town;
                                $carac_shipping .= ' '.$entrepot->country;
                            }
                        }
                    }
                }
                // Afficher l'adresse du contact
                elseif (empty($object->array_options['options_manual_shipping_site']) && empty($obj->array_options['options_shipping_site_supplier_order'])) {
                    $contact_type = 'SALESREPFOLL';
                    $arrayidcontact = $object->getIdContact('internal', $contact_type);
                    if (count($arrayidcontact) > 0) {
                        // require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php
                        $object->fetch_user($arrayidcontact[0]);
                        $carac_shipping .= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
                        $carac_shipping .= $object->user->address;
                        $carac_shipping .= '<br>'.$object->user->zip;
                        $carac_shipping .= ' '.$object->user->town;
                    } else {
                        // Afficher l'adresse de livraison manuelle de la fiche du tiers
                        if (!empty($object->thirdparty->array_options["options_shipping_address"])) {
                            $carac_shipping = $object->thirdparty->array_options["options_shipping_address"];
                        }
                    }
                }
            }

            $carac_shipping = dol_nl2br(str_replace('<br />', '', $carac_shipping), 1);

            if (isset($this->header_address_blocks['recipient'])) {

                // Show recipient
                $posy = $nexY;
                $posx = 110;
                $widthrecbox = 90;
                if (isset($this->header_address_blocks['recipient']['pos'])) {
                    $posx = $this->header_address_blocks['recipient']['pos'];
                }
                if (isset($this->header_address_blocks['recipient']['posy'])) {
                    $posy = $this->header_address_blocks['recipient']['posy'];
                }
                if (isset($this->header_address_blocks['recipient']['header_align'])) {
                    $header_align = $this->header_address_blocks['recipient']['header_align'];
                } else {
                    $header_align = 'L';
                }
                if (isset($this->header_address_blocks['recipient']['field_align'])) {
                    $field_align = $this->header_address_blocks['recipient']['field_align'];
                } else {
                    $field_align = 'L';
                }
                if (isset($this->header_address_blocks['recipient']['width'])) {
                    $widthrecbox = $this->header_address_blocks['recipient']['width'];
                }
                if (isset($this->header_address_blocks['recipient']['height'])) {
                    $hautcadre = $this->header_address_blocks['recipient']['height'];
                }
                if (isset($this->header_address_blocks['recipient']['bgcolor'])) {
                    $bgcolor = explode(',', $this->header_address_blocks['recipient']['bgcolor']);
                } else {
                    $bgcolor = $this->bgcolor;
                }
                if (isset($this->header_address_blocks['recipient']['padding_left'])) {
                    $padding_left = $this->header_address_blocks['recipient']['padding_left'];
                } else {
                    $padding_left = 2;
                }

                // Show recipient frame
                $pdf->SetTextColorArray($this->textcolor);
                $pdf->SetFont('', '', $this->default_font_size - 1);
                if ($object->element == 'shipping' || ! empty($this->use_delivery_address)) {
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("DeliveryAddress"), 0, $header_align);
                } elseif (!empty($object->array_options['options_rental_doc'])) {
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("RentTo"), 0, $header_align);

                } elseif ($this->name == 'supplierboard') {
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Supplier"), 0, $header_align);
                } elseif ($object->element == 'order_supplier') {
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo"), 0, $header_align);
                } else {
                    $pdf->SetXY($posx, $posy - 5);
                    $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillAddress"), 0, $header_align);
                }

                $pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);

                // Show recipient name
                $show_name = true;
                if ($object->element == 'shipping' && !empty($this->hide_customer_name_for_manual_shipping) && !empty($manual_shipping_site)) {
                    $show_name = false;
                }
                if ($show_name) {
                    $pdf->SetXY($posx + $padding_left, $posy + 2);
                    $pdf->SetFont('', 'B', $this->default_font_size);
                    $pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, $field_align);
                    $pdf->SetXY($posx + $padding_left, $posy + 3 + (dol_nboflines_bis($carac_client_name, 32) * 4)); //8
                } else {
                    $pdf->SetXY($posx + $padding_left, $posy + 2);
                }

                // Show recipient information
                $pdf->SetFont('', '', $this->default_font_size - 1);

                if ($object->element == 'shipping' || ! empty($this->use_delivery_address)) {
                    $pdf->writeHTMLCell($widthrecbox, 0, $pdf->getX(), $pdf->getY(), $carac_shipping, 0, 1, false, true, $field_align, true);
                } else {
                    $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $field_align);
                }
            }

            // Other informations
            if (!empty($this->hideotherinfos)) {
                return;
            }

            $pdf->SetFillColor(255, 255, 255);

            $nexY += 48;

            $memY = $maxy = $nexY;

            if (!empty($this->header_other_blocks)) {
                $nexX = $this->marge_gauche;

                foreach ($this->header_other_blocks as $name => $block) {
                    if (isset($block['hideif']) && verifCond($block['hideif'])) {
                        continue;
                    }
                    if (isset($block['showif']) &&  !verifCond($block['showif'])) {
                        continue;
                    }

                    if (isset($block['pos'])) {
                        $posx = $block['pos'];
                    } else {
                        $posx = $nexX;
                    }

                    $nexX += $block['width'] + 5;

                    $nexY = $memY;
                    if (isset($block['posy'])) {
                        switch (substr($block['posy'], 0, 1)) {
                            case "+":
                                $nexY += substr($block['posy'], 1);
                                break;
                            case "-":
                                $nexY = $nexY - substr($block['posy'], 1);
                                break;
                            default:
                                $nexY = $block['posy'];
                        }
                    }

                    $posy = $nexY + 6;
                    $widthblock = $block['width'];
                    if (isset($block['bgcolor'])) {
                        $bgcolor = explode(',', $block['bgcolor']);
                    } else {
                        $bgcolor = $this->bgcolor;
                    }
                    if (isset($block['header_align'])) {
                        $header_align = $block['header_align'];
                    } else {
                        $header_align = 'C';
                    }
                    if (isset($block['field_align'])) {
                        $field_align = $block['field_align'];
                    } else {
                        $field_align = 'C';
                    }
                    if (isset($block['padding_left'])) {
                        $padding_left = $block['padding_left'];
                    } else {
                        $padding_left = 0;
                    }
                    if (isset($block['header_font_weight'])) {
                        $header_font_weight = $block['header_font_weight'];
                    } else {
                        $header_font_weight = '';
                    }
                    if (isset($block['header_fill'])) {
                        $header_fill = $block['header_fill'];
                    } else {
                        $header_fill = '';
                    }

                    if (! isset($block['title'])) {
                        $block['title'] = $name;
                    }

                    if (! empty($block['title'])) {
                        $pdf->RoundedRect($posx, $nexY, $widthblock, 6, 2, $round_corner = '1111', $header_fill, $this->style, $bgcolor);
                        $pdf->SetXY($posx, $nexY + 1);
                        $pdf->SetTextColorArray($this->textcolor);
                        $pdf->SetFont('', $header_font_weight, $this->default_font_size - 1);
                        $pdf->MultiCell($widthblock, 5, $outputlangs->transnoentities($block['title']), 0, $header_align, false);
                        $posy += 1;
                    }

                    $type = $block["type"] ?? '';
                    if (empty($type)) {
                        $type = $name;
                    }
                    switch ($type) {
                        case 'Sender':
                            // Sender properties
                            $carac_emetteur = '';
                            // Add internal contact of proposal if defined
                            if (getDolGlobalString('PDF_HIDE_SALES_REP_IN_SENDER_FRAME')) {
                                if (isset($block['contact_type'])) {
                                    $contact_type = $block['contact_type'];
                                } else {
                                    $contact_type = 'SALESREPFOLL';
                                }
                                $arrayidcontact = $object->getIdContact('internal', $contact_type);
                                if (count($arrayidcontact) > 0) {
                                    $object->fetch_user($arrayidcontact[0]);
                                    $carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
                                }
                            }

                            $conf->global->MAIN_PDF_DISABLESOURCEDETAILS = $block['details'] == 'N' ;
                            $carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur);

                            // Show sender name center
                            $pdf->SetXY($posx + $padding_left, $posy);
                            $pdf->SetFont('', 'B', $default_font_size);
                            $pdf->MultiCell($widthblock, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $field_align);
                            $posy = $pdf->getY();

                            // Show sender information
                            $pdf->SetXY($posx + $padding_left, $posy);
                            $pdf->SetFont('', '', $default_font_size - 1);
                            $pdf->MultiCell($widthblock, 4, $carac_emetteur, 0, $field_align);

                            break;

                        case 'Recipient':
                            // If CUSTOMER contact defined, we use it
                            $usecontact = false;
                            if (!getDolGlobalString('PDF_HIDE_CONTACT_IN_CUSTOMER_FRAME')) {
                                if (isset($block['contact_type'])) {
                                    $contact_type = $block['contact_type'];
                                } else {
                                    $contact_type = 'CUSTOMER';
                                }
                                $arrayidcontact = $obj->getIdContact('external', $contact_type);
                                if (count($arrayidcontact) > 0) {
                                    $usecontact = true;
                                    $result = $obj->fetch_contact($arrayidcontact[0]);
                                }
                            }

                            if ($usecontact && getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
                                $thirdparty = $object->contact;
                            } else {
                                $thirdparty = $object->thirdparty;
                            }

                            $carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
                            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $obj->contact : ''), $usecontact, 'target', $object);

                            // Show recipient name
                            if (! empty($this->use_delivery_address)) {
                                $pdf->SetXY($posx + $padding_left, $posy);
                            } elseif (empty($shipping_site) || ($shipping_site == 'Client' && empty($obj->thirdparty->array_options['options_shipping_address']))) {
                                $pdf->SetXY($posx + $padding_left, $posy);
                                $pdf->SetFont('', '', $this->default_font_size);
                                $pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, $field_align);
                                $pdf->SetXY($posx + $padding_left, $posy + 3 + (dol_nboflines_bis($carac_client_name, 32) * 4)); //8
                            } else {
                                $pdf->SetXY($posx + $padding_left, $posy);
                            }

                            // Show recipient information
                            $pdf->SetFont('', '', $this->default_font_size - 1);

                            if ($object->element == 'shipping' || ! empty($this->use_delivery_address)) {
                                $pdf->writeHTMLCell($widthrecbox, 0, $pdf->getX(), $pdf->getY(), $carac_shipping, 0, 1, false, true, $field_align, true);
                            } else {
                                $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $field_align);
                            }
                            break;

                        case 'CommercialInterlocutor':
                            if (isset($block['contact_type'])) {
                                $contact_type = $block['contact_type'];
                            } else {
                                $contact_type = 'SALESREPFOLL';
                            }
                            $arrayidcontact = $object->getIdContact('internal', $contact_type);
                            $object->fk_user_author = $object->fk_user_author ?? '';
                            $object->user_author_id = $object->user_author_id ?? '';

                            if (! empty($arrayidcontact)) {
                                $object->fetch_user($arrayidcontact[0]);
                                $pdf->SetFont('', '', $this->default_font_size - 1);
                                $pdf->SetXY($posx, $posy);
                                $pdf->SetTextColorArray($this->textcolor);
                                $phone = !empty($object->user->user_mobile) ? "\n".$object->user->user_mobile : (!empty($object->user->office_phone) ? "\n".$object->user->office_phone : '');
                                $pdf->MultiCell($widthblock, 7, $this->emetteur->name."\n".$object->user->firstname.' '.$object->user->lastname.$phone.(!empty($object->user->email) ? "\n".$object->user->email : ""), '0', 'C');

                            } elseif (!empty($object->user_author_id) || ($object->element == 'facture' && !empty($object->fk_user_author))) {
                                $id_user = !empty($object->user_author_id) ? $object->user_author_id : $object->fk_user_author;
                                $object->fetch_user($id_user);
                                $pdf->SetFont('', '', $this->default_font_size - 1);
                                $pdf->SetXY($posx, $posy);
                                $pdf->SetTextColorArray($this->textcolor);
                                $pdf->MultiCell($widthblock, 7, $this->emetteur->name."\n".$object->user->firstname.' '.$object->user->lastname.(!empty($object->user->user_mobile) ? "\n".$object->user->user_mobile : "").(!empty($object->user->email) ? "\n".$object->user->email : ""), '0', 'C');
                                $pdf->SetTextColorArray($this->textcolor);
                            } else {
                                $pdf->SetFont('', '', $this->default_font_size - 1);
                                $pdf->SetXY($posx, $posy);
                                $pdf->SetTextColorArray($this->textcolor);
                                $pdf->SetFillColor(255, 255, 255);
                                $pdf->MultiCell($widthblock, 6, 'NR', '0', 'C');
                            }
                            break;

                        case 'DeliveryAddress':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetTextColorArray($this->textcolor);
                            if ($object->element != 'order_supplier') {
                                if ((!getDolGlobalString('ALTAPDF_HIDE_COMPANY_NAME_IN_SHIPPING_ADDRESS') && empty($object->array_options['options_shop']) && empty($manual_shipping_site) && empty($shipping_site)) || ($shipping_site == 'Client' && empty($obj->thirdparty->array_options['options_shipping_address'])) || (isset($shipping_contact) && is_object($shipping_contact) && $object->thirdparty != $shipping_contact)) {
                                    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                                    $shipping_contact  = new Societe($this->db);
                                    $shipping_contact->fetch($object->thirdparty->id);

                                    // N'est pas utile avec l'option site de livraison sur la commande fournisseur
                                    // if ($object->element == 'order_supplier') {
                                    //     $shipping_contact_name = $this->emetteur->name;
                                    // } else
                                    if (is_object($shipping_contact)) {
                                        $shipping_contact_name = $outputlangs->convToOutputCharset($shipping_contact->name);
                                    } else {
                                        $shipping_contact_name = $carac_client_name;
                                    }
                                    $pdf->SetXY($posx, $posy);
                                    $pdf->MultiCell($widthblock, 0, $shipping_contact_name, '0', 'C');
                                    $posy = $pdf->getY();// (dol_nboflines_bis($carac_client_name,35)*4); //8
                                }
                                $pdf->SetXY($posx, $posy);
                                if (!empty($object->array_options['options_shop'])) {
                                    $carac_shipping = 'SHOP';
                                }
                            }
                            $pdf->writeHTMLCell($widthblock, 0, $posx, $posy, $carac_shipping, 0, 1, false, true, 'C', true);
                            break;

                        case 'DateDeliveryPlanned':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);
                            $date = dol_print_date($object->delivery_date, 'day', '', 0, 'dd/MM/yy');
                            $pdf->MultiCell($widthblock, 7, $date, '0', 'C');
                            break;

                        case 'BillAddress':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);

                            $pdf->MultiCell($widthblock, 6, $carac_client_name, '0', 'C');
                            $pdf->SetXY($posx, $posy + (dol_nboflines_bis($carac_client_name, 38) * 4)); //8);

                            $conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = 1;
                            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, $obj->contact, false, 'target');

                            $pdf->MultiCell($widthblock, 6, $carac_client, '0', 'C');
                            break;

                        case 'CustomerContact':
                            if (isset($block['contact_type'])) {
                                $contact_type = $block['contact_type'];
                            } else {
                                $contact_type = 'CUSTOMER';
                            }
                            $arrayidcontact = $obj->getIdContact('external', $contact_type);

                            if (count($arrayidcontact) > 0) {
                                $object->fetch_contact($arrayidcontact[0]);
                            }

                            $text = '';
                            if (!empty($object->contact)) {
                                $tel = $object->contact->phone_pro ? $object->contact->phone_pro : ($object->contact->phone_mobile ? $object->contact->phone_mobile : ($object->contact->phone_perso ? $object->contact->phone_perso : ''));
                                $text = $object->contact->firstname.' '.$object->contact->lastname.(!empty($tel) ? "\n".$tel : "")."\n".$object->contact->email;
                            }
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);
                            $pdf->MultiCell($widthblock, 6, $outputlangs->convToOutputCharset($text), '0', 'C');
                            break;

                        case 'Carrier':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);
                            $pdf->MultiCell($widthblock, 6, $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'libelle'), '0', 'C');
                            $y = $pdf->getY();
                            $pdf->SetXY($this->marge_gauche, $y + 6);

                            // Tracking number
                            if (!empty($object->tracking_number)) {
                                $object->GetUrlTrackingStatus($object->tracking_number);
                                if (!empty($object->tracking_url) && strpos($object->tracking_url, 'http') > 0) {
                                    if ($object->shipping_method_id > 0) {
                                        // Get code using getLabelFromKey
                                        $code = $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
                                        $label = $outputlangs->trans("LinkToTrackYourPackage")."<br>";
                                        $label .= $outputlangs->trans("SendingMethod".strtoupper($code))." :";
                                        $pdf->SetFont('', '', $this->default_font_size - 2);
                                        $pdf->writeHTMLCell($widthblock, 6, $posx, $y, $label." ".$object->tracking_url, 0, 1, false, true, 'C');
                                    }
                                } else {
                                    $pdf->MultiCell($widthblock, 6, $object->tracking_number, '0', 'C');
                                }
                            }
                            break;

                        case 'Proposal':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);
                            $pdf->SetFillColor(255, 255, 255);
                            $pdf->MultiCell($widthblock, 6, $this->_get_ref_propal($object->id, $object->element), '0', 'C');
                            break;

                        case 'SenderDetails':
                            $details = '';
                            // Phone
                            if ($this->emetteur->phone) {
                                $details .= $outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($this->emetteur->phone);
                            }
                            // Fax
                            if ($this->emetteur->fax) {
                                $details .= ($details ? ($this->emetteur->phone ? " - " : "\n") : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
                            }
                            // EMail
                            if ($this->emetteur->email) {
                                $details .= ($details ? "\n" : '')/*.$outputlangs->transnoentities("Email").": "*/.$outputlangs->convToOutputCharset($this->emetteur->email);
                            }
                            // Web
                            if ($this->emetteur->url) {
                                $details .= ($details ? "\n" : '')/*.$outputlangs->transnoentities("Web").": "*/.$outputlangs->convToOutputCharset($this->emetteur->url);
                            }

                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetXY($posx, $posy);
                            $pdf->SetTextColorArray($this->textcolor);
                            $pdf->SetFillColor(255, 255, 255);
                            $pdf->MultiCell($widthblock, 6, $details, '0', 'L');
                            break;

                        case 'Image':
                            $img = dol_buildpath($block['src']);
                            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                            if (dol_is_file($img)) {
                                $pdf->Image($img, $posx, $nexY, $block['width'], $block['height']);
                            }
                            break;

                        case 'ShippingDate':
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetTextColorArray($this->textcolor);

                            $dateExpedition = (!empty($object->date_delivery) ? dol_print_date($object->date_delivery) : '');
                            $pdf->MultiCell($widthblock, 6, $dateExpedition, '0', 'C');
                            break;
                        case 'Comment':
                            // Commentaire sur les factures
                            $pdf->SetFont('', '', $this->default_font_size - 1);
                            $pdf->SetTextColorArray($this->textcolor);
                            $comment = $object->array_options['options_comment'] ?? '';
                            $pdf->writeHTMLCell($widthblock, 0, $posx, $posy, $comment, 0, 1, false, true, 'C', true);
                            break;
                    }
                    $maxy = max($maxy, $pdf->getY());
                }
            }

            $this->tab_top = max($maxy, $pdf->getY()) + (empty($object->array_options['options_rental_doc']) ? 6 : (empty($this->nb_rental_lines) ? 10 : 30));
        }

        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     *   	Show footer of page. Need this->emetteur object
     *
     *   	@param	PDF			$pdf     			PDF
     * 		@param	Object		$object				Object to show
     *      @param	Translate	$outputlangs		Object lang for output
     *      @return	void
     */
    public function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        global $conf;

        switch ($object->element) {
            case 'propal':
                $freetext = "PROPOSAL_FREE_TEXT";
                break;
            case 'commande':
                if ($this->proforma) {
                    $freetext = "INVOICE_FREE_TEXT";
                } else {
                    $freetext = "ORDER_FREE_TEXT";
                }
                break;
            case 'shipping':
                $freetext = "SHIPPING_FREE_TEXT";
                break;
            case 'facture':
                $freetext = "INVOICE_FREE_TEXT";
                break;
            case 'order_supplier':
                $freetext = "SUPPLIER_ORDER_FREE_TEXT";
                break;
        }

        $showdetails = getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');

        return pdf_pagefoot($pdf, $outputlangs, $freetext ?? '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
    }



    // ecriture lignes
    public function _printlines(&$pdf, $rental = false, $object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $hookmanager = false)
    {
        global $user, $langs, $conf, $cptline;

        $heightforavis = empty($rental) ? $this->heightforavisselling : $this->heightforavisrental;

        if ($object->element == 'order_supplier') {
            $lines = $this->curlines;
        } else {
            $lines = $object->lines;
        }

        // Loop on each lines
        $nblines = count($lines);
        if (!empty($this->no_specials) && !empty($object->lines[$nblines - 1]->special_code)) {
            $nblines--;
        } else {
			foreach ($lines as $i => $dummy) {
				if (!empty($this->no_specials_except_titles) && !empty($object->lines[$nblines - 1]->special_code)  && $object->lines[$i]->qty > 9) {
					$nblines--;
					break;
				}
			}
		}

        $cptline = 1;

        foreach ($lines as $i => $dummy) {
            if (!empty($this->no_specials) && !empty($object->lines[$i]->special_code)) {
                // avoid subtotal rental and selling lines
                continue;
            }

            if (!empty($this->no_specials_except_titles) && !empty($object->lines[$i]->special_code) && $object->lines[$i]->qty > 9) {
                // avoid subtotal rental and selling lines
                continue;
            }

            if (!getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                if (!empty($rental)	&& empty($lines[$i]->array_options['options_rental_line'])) {
                    $ok = false;

                    // test si titre
                    if ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty <= 9) {
                        if ($i == count($lines) - 1) {
                            $ok = true;
                        } else {
                            for ($j = $i + 1; $j < count($lines); $j++) {
                                if ($j == count($lines) - 1) {
                                    $ok = true;
                                    break;
                                }
                                if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                    continue;
                                } else {
                                    $ok = isset($lines[$j]) && !empty($lines[$j]->array_options['options_rental_line']);
                                    break;
                                }
                            }
                        }
                    }

                    // test si sous-total
                    elseif ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty == 99) {
                        if ($i == 0) {
                            $ok = true;
                        } else {
                            for ($j = $i - 1; $j >= 0; $j--) {
                                if ($j == 0) {
                                    $ok = true;
                                    break;
                                }
                                if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                    continue;
                                } else {
                                    $ok = isset($lines[$j]) && !empty($lines[$j]->array_options['options_rental_line']);
                                    break;
                                }
                            }
                        }
                    }

                    // test si free text
                    elseif ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty == 50) {
                        if ($i == 0) {
                            $ok = true;
                        } else {
                            for ($j = $i - 1; $j >= 0; $j--) {
                                if ($j == 0) {
                                    $ok = true;
                                    break;
                                }
                                if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                    continue;
                                } else {
                                    $ok = $i == 1 || (isset($lines[$i - 1]) && !empty($lines[$i - 1]->array_options['options_rental_line']));
                                    break;
                                }
                            }
                        }
                    }

                    if (! $ok) {
                        continue;
                    }
                }

                if (empty($rental)) {
                    if (!empty($lines[$i]->array_options['options_rental_line'])) {
                        continue;
                    } else {
                        $bad = true;

                        // test si titre
                        if ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty <= 9) {
                            if ($i == count($lines) - 1) {
                                $bad = false;
                            } else {
                                for ($j = $i + 1; $j < count($lines); $j++) {
                                    if ($j == count($lines) - 1) {
                                        $bad = false;
                                        break;
                                    }
                                    if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                        continue;
                                    } else {
                                        $bad = isset($lines[$j]) && !empty($lines[$j]->array_options['options_rental_line']);
                                        break;
                                    }
                                }
                            }
                        }

                        // test si sous-total
                        elseif ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty == 99) {
                            if ($i == 0) {
                                $bad = false;
                            } else {
                                for ($j = $i - 1; $j >= 0; $j--) {
                                    if ($j == 0) {
                                        $bad = false;
                                        break;
                                    }
                                    if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                        continue;
                                    } else {
                                        $bad = isset($lines[$j]) && !empty($lines[$j]->array_options['options_rental_line']);
                                        break;
                                    }
                                }
                            }
                        }

                        // test si free text
                        elseif ($lines[$i]->product_type == 9 && !empty($lines[$i]->special_code) && $lines[$i]->qty == 50) {
                            if ($i == 0) {
                                $bad = false;
                            } else {
                                for ($j = $i - 1; $j >= 0; $j--) {
                                    if ($j == 0) {
                                        $bad = false;
                                        break;
                                    }
                                    if (isset($lines[$j]) && !empty($lines[$j]->special_code)) {
                                        continue;
                                    } else {
                                        $bad = $i > 1 && isset($lines[$i - 1]) && !empty($lines[$i - 1]->array_options['options_rental_line']);
                                        break;
                                    }
                                }
                            }
                        }

                        // test total
                        else {
                            $bad = $lines[$i]->special_code == 140015 && $i + 1 == count($lines) && $this->total_selling == 0;
                        }

                        if ($bad) {
                            continue;
                        }
                    }
                }
            }

            // shipping cost line
            if (isModEnabled('shippingcost') && ($lines[$i]->product_type == getDolGlobalString('SHIPPING_PRODUCT_TYPE'))) {
                $this->shipping_ht += $lines[$i]->total_ht;
                if ($lines[$i]->tva_tx != 0) {
                    $this->shipping_vat[$lines[$i]->tva_tx] += $lines[$i]->total_tva;
                }
                $this->shipping_ttc += $lines[$i]->total_ttc;
            }

            if (!isModEnabled('shippingcost') || ($lines[$i]->product_type != getDolGlobalString('SHIPPING_PRODUCT_TYPE')) || getDolGlobalString('PRINT_SHIPPING_LINES')) {
                $this->curY = $this->nexY;

                $pdf->SetFont('', '', $this->default_font_size - 2);   // Dans boucle pour gerer multi-page - police plus petite pour entrer le tableau en entier

                $pdf->setTopMargin($this->next_tab_top_newpage + 10);
                $pdf->setPageOrientation('', 1, $this->heightforfooter);	// The only function to edit the bottom margin of current page to set it.
                $pageposbefore = $pdf->getPage();
                // product line
                $pdf->startTransaction();
                $this->nexY = $this->_writeline($pdf, $object, $i, $rental, $outputlangs, $this->curY, $hidedetails, $hideref, $hidedesc, $hookmanager) + 2;

                $pageposafter = $pdf->getPage();
                if ($pageposafter > $pageposbefore && $i >= 1) {	// There is a pagebreak
                    $pdf->rollbackTransaction(true);
                    $cptline--;
                    $this->AddNewPage($pdf, $object, $outputlangs, $hookmanager);
                    $curY = $this->tab_top_newpage + 10;
                    $this->nexY = $this->_writeline($pdf, $object, $i, $rental, $outputlangs, $curY, $hidedetails, $hideref, $hidedesc, $hookmanager) + 2;
                    $pageposafter = $pdf->getPage();
                }
                // Add line
                if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($this->nblines - 1) && empty($level)) {
                    $curY = max($this->nexY - 2, $pdf->getY());
                    $pdf->SetLineStyle($this->linestyle);
                    $pdf->line($this->marge_gauche, $curY + 1, $this->page_largeur - $this->marge_droite, $curY + 1);
                    $pdf->SetLineStyle(array('dash' => 0));
                }
                if ($pageposafter <= $pageposbefore) {
                    $pdf->commitTransaction();
                }

                $posyafter = max($pdf->GetY(), $this->nexY);
                if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $this->heightforfreetext + $this->heightforinfotot + $heightforavis))) {	// There is no space left for total+free text
                    if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
                        $this->AddNewPage($pdf, $object, $outputlangs, $hookmanager);
                        $this->nb_lig_tab = 0;
                        $this->nexY = $pdf->GetY();
                    }
                }


                $pageposafter = $pdf->getPage();

                $pdf->setPage($pageposbefore);
                $pdf->setTopMargin($this->marge_haute);
                $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

                $this->nb_lig_tab++;

                // Detect if some page were added automatically and output _tableau for past pages
                while ($this->pagenb < $pageposafter) {
                    $pdf->setPage($this->pagenb);
                    if ($this->pagenb == 1) {// && ($rental || empty($this->nb_rental_lines) || getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')))
                        $this->_tableau($pdf, $object, $rental, $this->tab_top, $this->page_hauteur - $this->tab_top - $this->heightforfooter, 0, $outputlangs, 0, 1);
                    } else {
                        $this->_tableau($pdf, $object, $rental, $this->next_tab_top_newpage, $this->page_hauteur - $this->next_tab_top_newpage - $this->heightforfooter, 0, $outputlangs, 0, 1);
                    }
                    $this->_pagefoot($pdf, $object, $outputlangs, 1);
                    $this->pagenb++;
                    $pdf->setPage($this->pagenb);
                    $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
                    if (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD')) {
                        $this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
                    }
                }
                // milestones pagebreak or subtotal pagebreak
                if ((isset($lines[$i + 1]->pagebreak) && $lines[$i + 1]->pagebreak) || ((isset($lines[$i + 1]->product_type) && $lines[$i + 1]->product_type == 9) && (isset($lines[$i + 1]->info_bits) && $lines[$i + 1]->info_bits > 0))) {
                    if ($this->pagenb == 1) {// && ($rental || empty($this->nb_rental_lines) || getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')))
                        $this->_tableau($pdf, $object, $rental, $this->tab_top, $this->page_hauteur - $this->tab_top - $this->heightforfooter, 0, $outputlangs, 0, 1);
                    } else {
                        $this->_tableau($pdf, $object, $rental, $this->next_tab_top_newpage, $this->page_hauteur - $this->next_tab_top_newpage - $this->heightforfooter, 0, $outputlangs, 0, 1);
                    }
                    $this->_pagefoot($pdf, $object, $outputlangs, 1);
                    // New page
                    $this->AddNewPage($pdf, $object, $outputlangs, $hookmanager);
                    $this->pagenb++;
                    $lines[$i + 1]->info_bits = 0; // to avoid subtotal module pagebreak
                    $this->nexY = $this->next_tab_top_newpage + 10;
                }

                // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
                if (!isModEnabled('shippingcost') || ($lines[$i]->product_type != getDolGlobalString('SHIPPING_PRODUCT_TYPE')) || !getDolGlobalString('PRINT_SHIPPING_VAT')) {
                    $tvaligne = $lines[$i]->total_tva;
                } else {
                    $tvaligne = 0;
                }
                $tvaligne = $lines[$i]->total_tva;
                // Open DSI -- LV -- Multicurrency -- Begin
                if (isModEnabled('multicurrency') && $object->multicurrency_tx != 1) {
                    $tvaligne = $lines[$i]->multicurrency_total_tva;
                }
                // commented by christophe battarelelse $tvaligne=$object->lines[$i]->total_tva;
                // Open DSI -- LV -- Multicurrency -- End

                $localtax1ligne = $lines[$i]->total_localtax1;
                $localtax2ligne = $lines[$i]->total_localtax2;

                // code was not used. Keeping it just in case
                // if (!empty($obj->remise_percent) && (!isModEnabled('shippingcost') || $lines[$i]->product_type != getDolGlobalString('SHIPPING_PRODUCT_TYPE'))) {
                //     $tvaligne -= ($tvaligne * $lines[$i]->remise_percent) / 100;
                //     $localtax1ligne -= ($localtax1ligne * $obj->remise_percent) / 100;
                //     $localtax2ligne -= ($localtax2ligne * $obj->remise_percent) / 100;
                // }

                if (!isModEnabled('shippingcost') || ($lines[$i]->product_type != getDolGlobalString('SHIPPING_PRODUCT_TYPE')) || !getDolGlobalString('PRINT_SHIPPING_VAT')) {
                    $vatrate = (string) $lines[$i]->tva_tx;
                } else {
                    $vatrate = 0;
                }

                // $localtax1rate=(string) $lines[$i]->localtax1_tx;
                // $localtax2rate=(string) $lines[$i]->localtax2_tx;

                //  if (($lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
                //  if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]=0;
                //  if (! isset($this->localtax1[$localtax1rate]))	$this->localtax1[$localtax1rate]=0;
                //  if (! isset($this->localtax2[$localtax2rate]))	$this->localtax2[$localtax2rate]=0;
                //  $this->tva[$vatrate] += $tvaligne;
                //  $this->localtax1[$localtax1rate]+=$localtax1ligne;
                //  $this->localtax2[$localtax2rate]+=$localtax2ligne;
            }

        }
    }


    /**
     *   Show table for lines
     *
     *   @param		PDF			$pdf     		Object PDF
     *   @param		string		$this->tab_top		Top position of table
     *   @param		string		$this->tab_height		Height of table (rectangle)
     *   @param		int			$this->nexY			Y (not used)
     *   @param		Translate	$outputlangs	Langs object
     *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
     *   @param		int			$hidebottom		Hide bottom bar of array
     *   @return	void
     */
    public function _tableau(&$pdf, $object, $rental, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
    {
        global $conf, $user;
        $outputlangs->load("stocks");		// Force to disable hidetop and hidebottom
        $hidebottom = 0;
        if ($hidetop) {
            $hidetop = -1;
        }

        // Open DSI -- LV -- Multicurrency -- Begin
        $currency_code = !empty($object->multicurrency_code) ? $object->multicurrency_code : $this->currency_code;
        // Open DSI -- LV -- Multicurrency -- End

        // rental / sell
        $pdf->SetTextColor(0, 0, 0);
        if ($rental) {
            if (!empty($this->rental_title) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                $pdf->SetFont('', 'B', $this->default_font_size + 1);
                if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
                    // Si il y a l'affichage de la catégorie des opérations
                    $pdf->SetXY($this->marge_gauche, $tab_top - 18);
                } else {
                    $pdf->SetXY($this->marge_gauche, $tab_top - 15);
                }
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 2, $outputlangs->transnoentities($this->rental_title), 0, 'C');
            }
            // Dates début et fin location
            $pdf->SetFont('', '', $this->default_font_size);
            if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
                // Si il y a l'affichage de la catégorie des opérations
                $pdf->SetXY($this->marge_gauche, $tab_top - (empty($this->rental_title) ? 15 : 9));
            } else {
                $pdf->SetXY($this->marge_gauche, $tab_top - (5 + (empty($this->rental_title) ? 10 : 0)));
            }
            $pdf->MultiCell(120, 2, $outputlangs->transnoentities("DateRentalStart").": ".dol_print_date($object->array_options['options_rental_start'], "day", false, $outputlangs, true). ' - '.$outputlangs->transnoentities("DateRentalEnd").": ".dol_print_date($object->array_options['options_rental_end'], "day", false, $outputlangs, true), 0, 'L');

        } elseif (!empty($this->nb_rental_lines)) {
            if (!empty($this->sell_title) && !getDolGlobalString('ALTAPDF_MIX_RENTAL_AND_SALES')) {
                $pdf->SetFont('', 'B', $this->default_font_size + 1);
                $pdf->SetXY($this->marge_gauche, $tab_top - 15);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 2, $outputlangs->transnoentities($this->sell_title), 0, 'C');
            }

            // commented by christophe battarel// Date validite proposition
            // commented by christophe battarel$pdf->SetFont('','',$this->default_font_size);
            // commented by christophe battarel$pdf->SetXY($this->marge_gauche, $tab_top -9 - (empty($this->sell_title)?6:0));
            // commented by christophe battarel$pdf->MultiCell(80, 2, $outputlangs->transnoentities("DateEndPropal").": ".dol_print_date($object->fin_validite,"day",false,$outputlangs,true),0,'L');

            // commented by christophe battarel// Delivery date -> Availability period
            // commented by christophe battarel$lib_availability=$outputlangs->transnoentities("AvailabilityType".$object->availability_code)!=('AvailabilityType'.$object->availability_code)?$outputlangs->transnoentities("AvailabilityType".$object->availability_code):$outputlangs->convToOutputCharset($object->availability);
            // commented by christophe battarel$lib_availability=str_replace('\n',"\n",$lib_availability);
            // commented by christophe battarel$pdf->SetFont('','',$this->default_font_size);
            // commented by christophe battarel$pdf->SetXY($this->marge_gauche, $tab_top -5 - (empty($this->sell_title)?6:0));
            // commented by christophe battarel$pdf->MultiCell(80, 2, $outputlangs->transnoentities("AvailabilityPeriod").": ".$lib_availability,0,'L');
        }


        // Montants exprimes en  (en tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $this->default_font_size - 2);

        if (empty($hidetop) && $object->element != 'shipping' && $object->element != 'return_productReturn' && $object->element != 'productreturn' && $object->element != 'facture') {
            // Open DSI -- LV -- Multicurrency -- Begin
            //$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$this->currency_code));
            $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency_code));
            // Open DSI -- LV -- Multicurrency -- End
            $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 2);
            $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

            if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
                $pdf->Rect($this->marge_gauche, $tab_top + 2, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
            }
        }

        if (empty($hidetop) && $object->element == 'facture') {
			// Show category of operations
			if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
				$categoryOfOperations = $outputlangs->transnoentities("MentionCategoryOfOperations") . ' : ' . $outputlangs->transnoentities("MentionCategoryOfOperations" . $this->categoryOfOperation);
				$pdf->SetXY($this->marge_gauche, $tab_top - 4);
				$pdf->MultiCell(($pdf->GetStringWidth($categoryOfOperations)) + 4, 2, $categoryOfOperations);
			}

			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency_code));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, $this->corner_radius, '1001', 'F', array(), explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}


        $pdf->SetFillColorArray($this->bgcolor);
        $pdf->SetTextColorArray($this->textcolor);
        $pdf->SetFont('', '', $this->default_font_size - 2);

        // Open DSI -- Adjust table border and column lines separator -- Begin
        $tabTopMargin = 2;
        $borderWidth  = $this->style['width'];
        $pdf->RoundedRect($this->marge_gauche, $tab_top + $tabTopMargin, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, 2, $round_corner = '1111', 'S', $this->style, array(200, 210, 234));

        // Permet de remplir le fond des en-têtes de colonnes du tableau
        if (getDolGlobalString('ALTAPDF_COL_HEADER_BACKGROUND')) {
            $pdf->RoundedRect($this->marge_gauche, $tab_top + $tabTopMargin, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 7, 2, $round_corner = '1001', 'F', '', $this->bgcolor);
        }

        // Permet de remplir le fond des en-têtes de colonnes du tableau
        if (getDolGlobalString('ALTAPDF_COL_HEADER_BACKGROUND')) {
            $pdf->RoundedRect($this->marge_gauche, $tab_top + $tabTopMargin, $this->page_largeur - $this->marge_gauche - $this->marge_droite, 7, 2, $round_corner = '1001', 'F', '', $this->bgcolor);
        }

        $pdf->line($this->marge_gauche + $borderWidth, $tab_top + $tabTopMargin + $borderWidth + 6, $this->page_largeur - $this->marge_droite - $borderWidth, $tab_top + $tabTopMargin + $borderWidth + 6, $this->style);//
        // Open DSI -- Adjust table border and column lines separator -- End

        // Open DSI -- Adjust table border and column lines separator -- Begin
        $colNum = 0;
        $colNb  = count($this->cols);
        // Open DSI -- Adjust table border and column lines separator -- End
        foreach ($this->cols as $field => $col) {
            $colx = $col['pos'] + $this->marge_gauche;
            if ($field == 'desc' || $field == 'refdesc' || $field == 'product_label') {
                $pos_desc = $col['pos'];
            }
            // commented by christophe battarelif ($field != 'remise_percent' || $this->atleastonediscount)
            // commented by christophe battarel{
            // commented by christophe battarelif(empty($this->atleastonediscount) && ($col['pos'] < $this->cols['remise_percent']['pos']) && ($colx > $pos_desc  && $pos_desc > 0))
            // commented by christophe battarel$colx += $this->cols['remise_percent']['length'];

            // Open DSI -- Adjust table border and column lines separator -- Begin
            if ($colNum < $colNb - 1) {
                $pdf->line($colx + $col['length'], $tab_top + $tabTopMargin, $colx + $col['length'], $tab_top + $tab_height + $tabTopMargin);//ligne fin de colonne
            }
            // Open DSI -- Adjust table border and column lines separator -- End
            if (empty($hidetop)) {
                $pdf->SetXY($colx, $tab_top + 3);
                $pdf->MultiCell($col['length'], 6, $outputlangs->transnoentities($col['header']), 0, $col['header_align'], 0);
            }
            // commented by christophe battarel}

            // Open DSI -- Adjust table border and column lines separator -- Begin
            $colNum++;
            // Open DSI -- Adjust table border and column lines separator -- End
        }


        $this->next_tab_top_newpage = $this->tab_top_newpage;
    }


    public function _get_ref_propal($id, $element)
    {
        global $conf;

        $ref_propal = '';

        $sql = "select fk_source, sourcetype from llx_element_element where fk_target = ".$id." and targettype = '".$element."'";
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                if ($obj->sourcetype == 'propal') {
                    require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                    $propal = new Propal($this->db);
                    $propal->fetch($obj->fk_source);
                    return $propal->ref;
                } else {
                    return $this->_get_ref_propal($obj->fk_source, $obj->sourcetype);
                }
            }
        }

        return '';

    }

    public function _writeline(&$pdf, $object, $i, $rental, $outputlangs, $curY, $hidedetails, $hideref, $hidedesc, $hookmanager, $level = 0)
    {
        global $user,$langs,$conf;

        // for _get_field_value function
        global $up_excl_tax, $qty, $qtyalreadyshipped, $remise_percent, $total_excl_tax, $vat_rate, $cptline;

        if ($object->element == 'shipping' && $level == 0) {
            if (!getDolGlobalString('ALTAPDF_PRINT_UNSHIPPED_LINES') && empty($object->lines[$i]->qty_shipped)) {
                $pdf->SetXY(0, $curY - 2);
                return ($curY - 2);
            }
            if (version_compare(DOL_VERSION, '20.0.0', '<')) {
                $qtyalreadyshipped = $this->getQtySent($object->lines[$i]->fk_product, $object->origin_id, $object->id, $object->lines[$i]->fk_origin_line);
            } else {
                $qtyalreadyshipped = $this->getQtySent($object->lines[$i]->fk_product, $object->origin_id, $object->id, $object->lines[$i]->origin_line_id);
            }
        }

        $pdf->SetFont('', '', $this->default_font_size - 2);   // On repositionne la police par defaut

        // currency amounts
        if ($rental && getDolGlobalString('RENTAL_WARRANTY_MANAGEMENT')) {
            $object->lines[$i]->total_ht = ($object->lines[$i]->total_ht - ($object->lines[$i]->array_options['options_rental_warranty_price'] * $qty)) * $this->currency_rate;
        } else {
            $object->lines[$i]->total_ht = $object->lines[$i]->total_ht;
        }
        // fait dans le hook du module currencybilling $object->lines[$i]->subprice *= $this->currency_rate;
        // fait dans le hook du module currencybilling $object->lines[$i]->total_tva *= $this->currency_rate;
        $object->lines[$i]->total_localtax1 *= $this->currency_rate;
        $object->lines[$i]->total_localtax2 *= $this->currency_rate;

        // Prix unitaire HT avant remise
        if ($object->lines[$i]->special_code != 140015) {
            $up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
        }
        // Quantity
        if ($object->lines[$i]->special_code != 140015) {
            $qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
        }
        // remise
        if (! empty($object->lines[$i]->remise_percent) && $object->lines[$i]->special_code != 140015) {
            $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails, $hookmanager);
        } else {
            $remise_percent = '';
        }
        // Total HT ligne
        if ($object->lines[$i]->remise_percent == 100) {
            $total_excl_tax = $remise_percent;
        } elseif ($object->lines[$i]->special_code != 140015) {
            $total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
        } else {
            $total_excl_tax = !empty((int) $object->lines[$i]->total_ht) ? price($object->lines[$i]->total_ht) : '';
        }
        // tva
        if ($object->lines[$i]->special_code != 140015) {
            $vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails, $hookmanager);
        }

        $desc_to_write = false;
        $maxY = $curY;
        $pageposbefore = $pdf->getPage();
		$nexY = 0;

        foreach ($this->cols as $field => $col) {
            if ($object->element != 'shipping' && $level > 0 && $field != 'qty' && $field != 'description' && $field != 'refdesc' && $field != 'product_label') {
                continue;
            }

            if ($object->element == 'shipping' && $level > 0 && ($field == 'line' || $field == 'ref')) {
                continue;
            }

            if (!empty($col['hideif']) && verifCond($col['hideif'])) {
                continue;
            }
            if (!empty($col['showif']) &&  !verifCond($col['showif'])) {
                continue;
            }

            $font_size = !empty($col['font_size']) ? $col['font_size'] : $this->default_font_size - 2;
            $pdf->SetFont('', ($object->lines[$i]->special_code == 140015) ? 'B' : '', $font_size);

            if ($field == 'desc' || $field == 'description' || $field == 'refdesc' || $field == 'product_label') {
                $desc_to_write = true;
                $pos_desc = $col['pos']; // used below
                $curX_desc = $col['pos'] + $this->marge_gauche;
                $len_desc = $col['length'];
                // commented by christophe battarelif(empty($this->atleastonediscount) && $col['pos'] < $this->cols['remise_percent']['pos'])
                // commented by christophe battarel{
                // commented by christophe battarel$len_desc += $this->cols['remise_percent']['length'];
                // commented by christophe battarel}

                $fontsize_desc = !empty($col['font_size']) ? $col['font_size'] : $this->default_font_size - 2;

            } else {
                $img = null;
                $text = '';

                if (! empty($field)) {
                    $this->_get_field_value($field, $text, $img, $object, $i, $rental, $outputlangs);
                }

                // function call
                if (!empty($col['function'])) {
                    $parameters = array(); // ! important

                    if (!empty($col['parameters'])) {
                        $pos = strpos($col['parameters'], ',');
                        foreach (explode(',', $col['parameters']) as $parm) {
                            $parm = trim($parm);
                            if (substr($parm, 0, 1) == '$') {
                                $parm = substr($parm, 1);
                                $this->_get_field_value($parm, $text, $img, $object, $i, $rental, $outputlangs);
                                $parameters[] = $text;
                            } else {
                                $parameters[] = $parm;
                            }
                        }
                    } else {
                        $parameters[] = $text;
                    }

                    if (empty($col['context'])) {
                        // appel fonction incluse (price, price2num, etc)
                        $text = call_user_func_array($col['function'], $parameters);
                    } elseif (substr($col['context'], 0, 1) == '$') {
                        // appel fonction sur objet ($this, $object, ...)
                        $contextobject = substr($col['context'], 1);
                        $text = call_user_func_array(array($$contextobject, $col['function']), $parameters);
                    } else {
                        // appel fonction de classe
                        $text = call_user_func_array(array($col['context'], $col['function']), $parameters);
                    }
                }


                if ($object->lines[$i]->special_code != 140015 || $col['field'] == 'totalht') {
                    $colx = $col['pos'] + $this->marge_gauche;
                    // commented by christophe battarelif(empty($this->atleastonediscount) && ($col['pos'] < $this->cols['remise_percent']['pos']) && ($col['pos'] > $pos_desc && $pos_desc > 0))
                    // commented by christophe battarel$colx += $this->cols['remise_percent']['length'];

                    if (!empty($img) && is_readable($img)) {
                        $imgwidth = (int) $col['length'];
                        $imgrealsize = dol_getImageSize($img);
                        $rate = $imgwidth / $imgrealsize['width'] ;
                        // force Image to col width
                        $pdf->Image($img, $colx, $curY, $imgwidth, 0, '', '', '', 2, 300);	// Use 300 dpi
                        // $pdf->Image does not increase value return by getY, so we save it manually
                        $forcedheight = $imgrealsize['height'] * $rate;
                        $newY = $curY + $forcedheight;
                        $pdf->SetXY($colx, $newY);
                    } else {
                        $pdf->SetXY($colx, $curY);
                        $coly = $curY;
                        $pdf->writeHTMLCell($col['length'], 4, $colx, $coly, $text, 0, 0, 0, 1, $col['field_align']);
                    }
                }

                $maxY = max($maxY, $pdf->getY());
            }
        }
        // description
        if ($desc_to_write) {
            $issupplierline = strpos($object->element, 'supplier') !== false;
            $pdf->SetFont('', ($object->lines[$i]->special_code == 140015) ? 'B' : '', $fontsize_desc);
            pdf_writelinedesc($pdf, $object, $i, $outputlangs, $len_desc, 0, $curX_desc, $curY, $hideref, $hidedesc, $issupplierline);
        }

        $curY = $pdf->getY();

        if ($object->element == 'shipping' || ($object->element == 'facture' && !isModEnabled('orderboard'))) {
            // Ajout du numéro de série, s'il existe...
            if (isModEnabled('equipement')) {
                $strTemp = $this->_getEquipementSerialDesc($object, $outputlangs, $i);
                if (!empty($strTemp)) {
                    $pdf->writeHTMLCell($len_desc, 3, $curX_desc, $curY, $outputlangs->convToOutputCharset($strTemp), 0, 1);
                    $curY = $curY + 4;
                    $nexY = $nexY + 4;
                }
            }
        }

        if (getDolGlobalInt('ALTAPDF_CUSTOMCODE_COUNTRY')) {
            // Affiche la monenclature (code SH) + pays d'origine si la description est cachée (option du core) et que l'on choisi d'afficher la monenclature (option altapdf)
            if (!empty($object->lines[$i]->fk_product)) {
                $customcode_country = '';
                dol_include_once('/product/class/product.class.php');
                $product = new Product($this->db);
                $product->fetch($object->lines[$i]->fk_product);
                if (!empty($product->customcode)) {
                    $customcode_country = $outputlangs->transnoentitiesnoconv("CustomCode").': '.$product->customcode;
                }
                if (!empty($product->customcode) && !empty($product->country_code)) {
                    $customcode_country .= ' - ';
                }
                if (!empty($product->country_code)) {
                    $customcode_country .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($product->country_code, 0, $this->db, $outputlangs, 0);
                }
            }
            $pdf->writeHTMLCell($len_desc, 3, $curX_desc, $curY, $customcode_country, 0, 1);
            $curY = $curY + 4;
            $nexY = $nexY + 4;
        }

        // composants
        $hidePackDetails = !empty(GETPOST('hidePackDetails')) ? GETPOST('hidePackDetails') : getDolGlobalInt('ALTAPDF_FORCE_HIDEPACK_DETAILS');
        $_SESSION['altapdf_hidePackDetails'.$object->element][$object->id] = $hidePackDetails;
        if (empty($hidePackDetails)) {
            if (!empty($object->lines[$i]->fk_product)) {
                dol_include_once('/product/class/product.class.php');
                $productstatic = new Product($this->db);
                $components = $productstatic->getChildsArbo($object->lines[$i]->fk_product);
                if (count($components) > 0) {
                    $curX = $curX_desc;
                    dol_include_once('/commande/class/commande.class.php');
                    $j = 0;
                    $nextlevel = $level + 1;
                    $alreadyshipped = $qtyalreadyshipped;
                    foreach ($components as $component) {
                        $cp = new Commande($this->db);
                        $cp->id = $object->id;
                        $cp->lines = array();
                        $cp->lines[$j] = new OrderLine($this->db);
                        if ($nextlevel == 1) {
                            $cp->lines[$j]->fk_origin_line = $object->lines[$i]->id;
                            $cp->lines[$j]->id = $object->lines[$i]->id;
                            $cp->lines[$j]->product_label = " ---> ".$component[3];
                        } else {
                            $cp->lines[$j]->fk_origin_line = $object->lines[$i]->fk_origin_line;
                            $cp->lines[$j]->id = $object->lines[$i]->fk_origin_line;
                            $cp->lines[$j]->product_label = "";
                            for ($l = 1; $l <= $nextlevel; $l++) {
                                $cp->lines[$j]->product_label .= "---";
                            }
                            $cp->lines[$j]->product_label .= "> ".$component[3];
                        }

                        $cp->lines[$j]->description = $cp->lines[$j]->product_label;
                        $cp->lines[$j]->fk_product = $component[0];
                        $cp->lines[$j]->type = $component[2];
                        $cp->lines[$j]->qty = $object->lines[$i]->qty * $component[1];
                        if ($object->element == 'shipping') {
                            $qtyalreadyshipped = $alreadyshipped * $component[1];
                            $cp->lines[$j]->qty_shipped = $object->lines[$i]->qty_shipped * $component[1];
                            $object->lines[$i]->not_send = $object->lines[$i]->not_send ?? 0;
                            $cp->lines[$j]->not_send = $object->lines[$i]->not_send * $component[1];
                        }

                        $this->_writeline($pdf, $cp, $j, $rental, $outputlangs, $curY, $hidedetails, $hideref, $hidedesc, $hookmanager, $nextlevel);
                        $curY = $pdf->getY();
                        $j++;
                    }
                }
            }
        }

        $curY = $pdf->getY();

        // commented by christophe battarel$pageafter=$pdf->getPage();
        // commented by christophe battarelif ($pageafter > $pagebefore) $maxY = $curY;
        // commented by christophe battarelelse
        // commented by christophe battarel
        $maxY = max($maxY, $curY);

        if (!empty($lecel) == 0) {
            $cptline++;
        }

        return max($maxY, $pdf->getY());
    }

    public function AddNewPage(&$pdf, $object, $outputlangs, $hookmanager)
    {
        global $conf;

        $pdf->AddPage('', '', true);

        $this->nb_lig_tab = 0;

        if (! empty($tplidx)) {
            $pdf->useTemplate($tplidx);
        }
        if (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD')) {
            $this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
        }

    }

    public function getXMLDescription($document)
    {
        $this->cols = array();
        $this->header_address_blocks = array();
        $this->header_other_blocks = array();

        if (empty($this->xmldesc)) {
            $this->xmldesc = get_class($this).'.xml';
        }

        $dom = new DomDocument();
        if ($document == 'propal') {
            $document = 'propale';
        }

        $xml_path = '';
        if (! empty($this->module)) {
            $xml_path = '/'.$this->module;
        }
        $dirdoc = (int) DOL_VERSION >= 12 ? 'doc' : 'pdf';
        $xml_path .= '/core/modules/'.($document == 'order_supplier' ? 'supplier_order/'.$dirdoc.'/' : $document.'/doc/').$this->xmldesc;

        $dom->load(dol_buildpath($xml_path));

        $cols = $dom->getElementsByTagName("column");
        foreach ($cols as $col) {
            $field = $col->getAttribute('field');
            foreach ($col->attributes as $attribute => $node) {
                $this->cols[$field][$attribute] = $node->value;
            }
        }

        $blocks = $dom->getElementsByTagName("header_address_block");
        foreach ($blocks as $block) {
            $name = $block->getAttribute('name');
            foreach ($block->attributes as $attribute => $node) {
                $this->header_address_blocks[$name][$attribute] = $node->value;
            }
        }

        $blocks = $dom->getElementsByTagName("header_other_block");
        foreach ($blocks as $block) {
            $name = $block->getAttribute('name');
            foreach ($block->attributes as $attribute => $node) {
                $this->header_other_blocks[$name][$attribute] = $node->value;
            }
        }
    }

    /**
     * Récupère la ligne de description à ajouter avec le n° de série de l'équipement pour la ligne $i
     *
     * @return String contenant le texte à afficher si un n° de série associé est trouvé.
     * @author
     */
    public function _getEquipementSerialDesc($object, $outputlangs, $i)
    {

        global $db, $conf;
		/** @var DoliDB $db */

        $retStr = '';

        $sql  = 'SELECT eq.rowid, eq.ref FROM '.MAIN_DB_PREFIX.'equipement AS eq, '.MAIN_DB_PREFIX.'equipementevt AS eqv ';
        $sql .= ' WHERE eq.rowid = eqv.fk_equipement ';
        if ($object->element == 'shipping') {
            $sql .= ' AND eqv.fk_expedition = "'.$object->id.'" ';
        } elseif ($object->element == 'facture') {
            $sql .= ' AND eq.fk_facture = "'.$object->id.'" ';
        }
        if (isModEnabled('orderboard')) {
            $sql .= ' AND eqv.fk_commandedet = "'.$object->lines[$i]->fk_origin_line.'" ';
        } else {
            $sql .= ' AND eq.fk_product = "'.$object->lines[$i]->fk_product.'" ';
        }

        dol_syslog("PDF_Expedition_Rouget_Equipement::_getEquipementSerialDesc - SQL = ".$sql);

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            if ($num > 0) {
                $retStr = $outputlangs->trans("SerialNumbers").':&nbsp;';
                for ($i = 0; $i < $num; $i++) {
                    $objp = $db->fetch_object($result);
                    $retStr .= ($i > 0 ? ' ; ' : '').$objp->ref;
                }
            }
        } else {
            $retStr = $outputlangs->transnoentities("NoResults");
        }

        return $retStr;
    }

    public function getQtySent($fk_product, $orderid, $shippingid, $orderlineid)
    {
        global $db, $conf;
		/** @var DoliDB $db */

        $sql = "SELECT sum(ed.qty) as qtysent";
        $sql .= " FROM ".MAIN_DB_PREFIX."element_element";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expedition e ON fk_target = e.rowid";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet ed ON e.rowid = ed.fk_expedition";
        $sql .= " WHERE e.fk_statut > 0 and e.rowid <> ".$shippingid;
        $sql .= " AND fk_source = ".$orderid." AND sourcetype = 'commande' AND targettype = 'shipping'";
        if (version_compare(DOL_VERSION, '20.0.0', '<')) {
            $sql .= " AND fk_origin_line = ".$orderlineid;
        } else {
            $sql .= " AND ed.fk_elementdet  = ".$orderlineid;
        }

        $result = $db->query($sql);
        if ($result) {
            $objp = $db->fetch_object($result);
            return empty($objp->qtysent) ? 0 : $objp->qtysent;
        }
        return 0;
    }

	/**
	 * Value for document table field
	 *
	 * @param string $field
	 * @param string $text
	 * @param string $img
	 * @param CommonObject $object
	 * @param int $i
	 * @param bool $rental
	 * @param Translate $outputlangs
	 * @return void
	 */
    public function _get_field_value($field, &$text, &$img, $object, $i, $rental, $outputlangs)
    {
        global $user,$langs,$conf;
        global $up_excl_tax, $qty, $qtyalreadyshipped, $remise_percent, $total_excl_tax, $vat_rate, $cptline;

        if (empty($field)) {
            return;
        }

        if (substr($field, 0, 16) == 'product_options_' && !empty($object->lines[$i]->fk_product)) {
            dol_include_once('/product/class/product.class.php');
            $productstatic = new Product($this->db);
            $productstatic->fetch_optionals($object->lines[$i]->fk_product);
            $text = $productstatic->array_options[str_replace('product_', '', $field)];
        } elseif (substr($field, 0, 8) == 'product_' && !empty($object->lines[$i]->fk_product)) {
            dol_include_once('/product/class/product.class.php');
            $productstatic = new Product($this->db);
            $productstatic->fetch($object->lines[$i]->fk_product);
            $text = $productstatic->{str_replace('product_', '', $field)};
        } elseif (substr($field, 0, 8) == 'options_') {
            $text = $object->lines[$i]->array_options[$field] ?? '';
        } else {
            switch ($field) {
                case 'line':
                    if ($object->element == 'shipping') {
                        $text = $cptline;
                    } else {
                        $text = ($i + 1);
                    }
                    break;
                case 'puht':
                    $text = $up_excl_tax;
                    break;
                case 'qty':
                    $text = $qty;
                    break;
                case 'already_shipped':
                    $text = $qtyalreadyshipped;
                    break;
                case 'qty_shipped':
                    $text = $object->lines[$i]->qty_shipped;
                    break;
                case 'not_send':
                    $text = $object->lines[$i]->qty_asked - $qtyalreadyshipped  - $object->lines[$i]->qty_shipped;
                    break;
                case 'remise_percent':
                    // commented by christophe battarelif(empty($this->atleastonediscount)) continue 2;
                    $text = $remise_percent;
                    break;
                case 'puremise':
                    if ($object->element == 'commande') {
                        $text = price($object->lines[$i]->price, 0, $outputlangs);
                    } elseif ($object->element == 'facture') {
                        $up_excl_tax = str_replace(',', '.', $up_excl_tax);
                        $pu_remise = ($up_excl_tax / 100) * $object->lines[$i]->remise_percent;
                        $pu_remise = $up_excl_tax - $pu_remise;
                        $text = price($pu_remise, 0, $outputlangs);
                    }
                    break;
                case 'discounted_up':
                    $sign = 1;
                    if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
                        $sign = -1;
                    }
                    if (empty($hidedetails) || $hidedetails > 1) {
                        $subprice = (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
                        $result = price($sign * $subprice, 0, $outputlangs);
                    }
                    $text = price($sign * $subprice * (1  - $object->lines[$i]->remise_percent / 100), 0, $outputlangs);
                    break;
                case 'totalht':
                    $text = $total_excl_tax;
                    break;
                case 'vat':
                case 'tva':
                    $text = $vat_rate;
                    break;
                case 'barcode':
                    $product = new Product($this->db);
                    if ($product->fetch($object->lines[$i]->fk_product)) {
                        // Barcode image
                        if (empty($product->barcode_type_code) || empty($product->barcode_type_coder)) {
                            $result = $product->fetch_barcode();
                        }

                        $img = $conf->barcode->dir_temp.'/barcode_'.$product->barcode.'_'.$product->barcode_type_code.'.png';
                        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
                        if (!dol_is_file($img)) {
                            // Load barcode class
                            global $genbarcode_loc, $bar_color, $bg_color, $text_color, $font_loc;
                            $classname = "mod".ucfirst($product->barcode_type_coder);
                            require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/'.$product->barcode_type_coder.'.modules.php';

                            $module = new $classname($this->db);
                            if ($module->encodingIsSupported($product->barcode_type_code)) {
                                $result = $module->writeBarCode($product->barcode, $product->barcode_type_code, true, 1.75);
                            }
                        }
                    } else {
                        $text = '';
                    }
                    break;
                case 'photo':
                    $objphoto = new Product($this->db);
                    if ($object->lines[$i]->fk_product > 0 && $objphoto->fetch($object->lines[$i]->fk_product)) {
                        //var_dump($objphoto->ref);exit;
                        if (getDolGlobalString('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
                            $pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id ."/photos/";
                            $pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product') . dol_sanitizeFileName($objphoto->ref).'/';
                        } else {
                            $pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product') . dol_sanitizeFileName($objphoto->ref).'/';				// default
                            $pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id ."/photos/";	// alternative
                        }

                        $arephoto = false;
                        foreach ($pdir as $midir) {
                            if (! $arephoto) {
                                $dir = $conf->product->dir_output.'/'.$midir;

                                foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
                                    if (!getDolGlobalString('CAT_HIGH_QUALITY_IMAGES')) {		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
                                        if ($obj['photo_vignette']) {
                                            $filename = $obj['photo_vignette'];
                                        } else {
                                            $filename = $obj['photo'];
                                        }
                                    } else {
                                        $filename = $obj['photo'];
                                    }

                                    $realpath = $dir.$filename;
                                    $arephoto = true;
                                }
                            }
                        }

                        if ($realpath && $arephoto) {
                            $img = $realpath;
                        }
                    }
                    $text = '';
                    break;
                case 'weight':
                    $weighttxt = '';
                    if ($object->lines[$i]->fk_product_type == 0 && $object->lines[$i]->weight) {
                        $weight = round($object->lines[$i]->weight * $object->lines[$i]->qty_shipped, 5);
                        $weighttxt = $weight.' '.measuring_units_string($object->lines[$i]->weight_units, "weight", '', 1);
                        if ($object->lines[$i]->weight_units == $object->weight_units) {
                            $object->weight += $weight;
                        } elseif (!empty($object->weight_units)) {
                            $object->weight += weight_convert($weight, $object->lines[$i]->weight_units, $object->weight_units);
                        }
                    }
                    $voltxt = '';
                    if (empty($object->lines[$i]->fk_product_type) && $object->lines[$i]->volume) {
                        $voltxt = round($object->lines[$i]->volume * $object->lines[$i]->qty_shipped, 5).' '.measuringUnitString(0, "volume", $object->lines[$i]->volume_units ? $object->lines[$i]->volume_units : 0, 1);
                    }
                    $text = $weighttxt.(($weighttxt && $voltxt) ? '<br>' : '').$voltxt;
                    break;
                case 'warehouse':
                    $warehouse = new Entrepot($this->db);
                    if ($object->element == 'shipping' && !empty($object->lines[$i]->entrepot_id && $warehouse->fetch($object->lines[$i]->entrepot_id))) {
                        $text = $warehouse->label;
                    } else {
                        $text = '';
                    }
                    break;
                    // Pour le PDF des commandes client et fournisseur
                case 'winereseller_millesime':
                    $text = $object->lines[$i]->array_options["options_winereseller_millesime"] ?? '';
                    break;
                case 'winereseller_contenance':
                    $text = price2num($object->lines[$i]->array_options["options_winereseller_contenance"]) ?? '';
                    break;
                    // Pour le PDF des commandes fournisseur
                case 'pourcentage_comm':
                    // utilisé pour les commissions agent d’orderboard
					// extraire la commande client
                    $object->fetchObjectLinked();
					if (isset($object->linkedObjects["commande"])) {
						foreach ($object->linkedObjects["commande"] as $index => $commande) {
							$order_customer = $commande;
						}
						// identifier le client
						dol_include_once('/societe/class/societe.class.php');
						$client = new Societe($this->db);
						$client->fetch($order_customer->socid);

						// Taux de commissions par prix client
						$sql_customer  = "SELECT pcpe.tms, pcpe.orderboard_commissions_rate, pcp.price";
						$sql_customer .= " FROM " . MAIN_DB_PREFIX . "product_customer_price_extrafields pcpe";
						$sql_customer .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_customer_price pcp ON pcpe.fk_object = pcp.rowid";
						$sql_customer .= " WHERE pcp.fk_product = " .$object->lines[$i]->fk_product;
						$sql_customer .= " AND pcp.fk_soc = " .$client->id;
						$sql_customer .= " AND pcp.entity = ". $conf->entity;
						$sql_customer = $this->db->query($sql_customer);
						if ($sql_customer && $this->db->num_rows($sql_customer) > 0) {
							$taux_com = $this->db->fetch_object($sql_customer);
						} else {
							// Taux de commissions par niveau de prix
							$resql_level  = "SELECT ppe.tms, ppe.orderboard_commissions_rate, pp.price_level";
							$resql_level  .= " FROM " . MAIN_DB_PREFIX . "product_price_extrafields ppe";
							$resql_level .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_price pp ON ppe.fk_object = pp.rowid";
							$resql_level .= " WHERE pp.fk_product = " .$object->lines[$i]->fk_product;
							$resql_level .= " AND pp.price_level = " .$client->price_level;
							$resql_level .= " AND pp.entity = ". $conf->entity;
							$resql_level .= " ORDER BY ppe.tms DESC";
							$resql_level = $this->db->query($resql_level);
							if ($resql_level && $this->db->num_rows($resql_level) > 0) {
								$taux_com = $this->db->fetch_object($resql_level);
							}
						}
						// on affiche le taux de commission du prix client sinon du niveau de prix
						$text = price($taux_com->orderboard_commissions_rate, 0, $outputlangs);
					}
                    break;
                case 'montant_comm':
                    $text =  price($object->lines[$i]->array_options["options_orderboard_commissions_amount"], 0, $outputlangs);
                    break;
                default:
                    $text = $object->lines[$i]->$field ?? '';
                    // retourne qty_returned et commentaire PDF locanco
                    break;
            }
        }
    }
}
