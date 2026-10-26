<?php
/* Copyright (C) 2021      Nicolas Silobre      <nicolas.silobre@it-bs.fr>



*/

require_once DOL_DOCUMENT_ROOT.'/core/modules/mrp/modules_mo.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';


class pdf_ofpdfgrp extends ModelePDFMo
{
    	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *²
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "mrp","bompdf@bompdf"));

		$this->db = $db;
		$this->name = "OF - produits à consommer";
		$this->description = $langs->trans('OfPdfgrpDescription');

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Affiche logo
		$this->option_freetext = 1; // Support add of a personalised text


		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxref=$this->marge_gauche+1;
		$this->posxdesc = 20 + $this->marge_gauche + 1;
		$this->posxqty = 155;
		$this->posxunit = 180;

        $this->xentetelib = $this->marge_gauche + 1;
		$this->xentetedata = $this->marge_gauche + 51;

		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxpicture -= 20;
			$this->posxtva -= 20;
			$this->posxup -= 20;
			$this->posxqty -= 20;
			$this->posxunit -= 20;
			$this->posxdiscount -= 20;
			$this->postotalht -= 20;
		}

	}
// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Object		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int             				1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		$unit = new CUnits($db);
		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "mrp", "bompdf@bompdf"));

		$nblines = count($object->lines);
		
		if ($conf->mrp->dir_output) {
			// $object->fetch_thirdparty();

			// $deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->mrp->dir_output;
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->mrp->dir_output."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$heightforinfotot = 50; // Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("CommercialAsk"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Mo")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->fk_product) {
						$this->atleastonediscount++;
					}
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 62;
				
				// Affiche notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE)) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) {
							$notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
						}
					}
				}
				if ($notetoshow) {
					$tab_top -= 2;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_top = $nexY + 6;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$objproduct = new Product($db);
				$prdaconso = '';

				for ($i = 0; $i < $nblines; $i++) 
				{
					$objproduct->fetch($object->lines[$i]->fk_product);
					if ($object->lines[$i]->role == 'toconsume')
					{
						$prdaconso .= $objproduct->ref. "  ";	
					}
					
				}
				$posy = $tab_top;
				$hautcadrey = $posy;
				$hautcadrex = $this->marge_gauche;
				$posx = $this->marge_gauche;
				$largeur = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
				// $this->printRect($pdf, $posx, $posy, $largeur, 25); // Rect takes a length in 3rd parameter and 4th parameter
				// $pdf->line($posx + 50, $posy, $posx + 50, $posy + 25);

				//Nomenclature
				$posy += 1;
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Nomenclature"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$bom = new BOM($db);
				$bom->fetch($object->fk_bom);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($this->xentetedata, 4, $outputlangs->convToOutputCharset($bom->ref, '', 'L'));
				
				//Produit
				$posy += 4;
				$objprd = new Product($db);
				$objprd->fetch($object->fk_product);
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Product"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell(0, 4, $objprd->ref." ".$outputlangs->convToOutputCharset($objprd->label), '', 'L');
				$posy = $pdf->GetY();

				//Quantité à produire
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("qtyprod"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($this->xentetedata, 4, $outputlangs->convToOutputCharset($object->qty), '', 'L');

				//Libellé
				$posy += 4;
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Label"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($this->xentetedata, 4, $outputlangs->convToOutputCharset($object->label), '', 'L');

				// //Entrepôt pour la fabrication
				$posy += 4;
				$objwareh = new Entrepot($db);
				$objwareh->fetch($object->fk_warehouse);
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("WarehouseForProduction"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($this->xentetedata, 4, $outputlangs->convToOutputCharset($objwareh->ref), '', 'L');

				//Date début/fin prévue
				$posy += 4;
				$pdf->SetXY($this->xentetelib, $posy);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Datedebfinpre"), '', 'L');
				$pdf->SetXY($this->xentetedata, $posy);
				$pdf->SetFont('', '', $default_font_size - 2);
                $datedeb = !empty($object->date_start_planned) ? date("d/m/Y", $object->date_start_planned) : '';
				$dateprev = $datedeb;
				if ($object->date_end_planned != NULL)
				{
					$datefin = date("d/m/Y", $object->date_end_planned);
					$dateprev .= " / ".$datefin;
				}

				$pdf->MultiCell($this->xentetedata, 4, $dateprev, '', 'L');

				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$this->printRect($pdf, $hautcadrex, $hautcadrey, $largeur, $posy-$hautcadrey); // Rect takes a length in 3rd parameter and 4th parameter
				$pdf->line($posx + 50, $hautcadrey, $posx + 50, $posy);

				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', 'BU', $default_font_size - 1);
				$posy += 8;
				$pdf->SetXY($this->xentetelib, $posy);
				
				$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, "Produit à consommer : ",'','L');

				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $prdaconso, '', 'J');

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}


				$pdf->Close();

				$pdf->Output($file, 'F');

				//Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1; // No error
				
			}
		}

	}

		// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		// $pdf->SetFont('', '', $default_font_size - 2);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetXY($this->posxdesc , $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');

		$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5); // line takes a position y in 2nd parameter and 4th parameter

		$pdf->SetXY($this->posxref - 1, $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Ref"), '', 'L');
		$pdf->line($this->posxdesc, $tab_top, $this->posxdesc, $tab_top + $tab_height);

		$pdf->SetXY($this->posxqty - 1, $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Qty"), '', 'L');
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty, $tab_top + $tab_height);

		$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Unit"), '', 'L');
		$pdf->line($this->posxunit-1, $tab_top, $this->posxunit, $tab_top + $tab_height);





	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf,$langs,$db;

		$outputlangs->loadLangs(array("main", "other", "mrp", "companies", "ofpdf@ofpdf"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		if ($object->statut == 0 && (!empty($conf->global->BOM_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->SUPPLIER_PROPOSAL_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("MO");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$pdf->SetFont('', '', $default_font_size - 2);
		//tiers
		$posy += 5;
		$soc = new Societe($db);
		$soc->fetch($object->fk_soc);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Soc")." : ".$outputlangs->convToOutputCharset($soc->nom), '', 'R');

		//Projet
		$posy += 4;
		$objprj = new Project($db);
		$objprj->fetch($object->fk_project);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Project")." : ".$outputlangs->convToOutputCharset($objprj->ref)." - ".$outputlangs->convToOutputCharset($objprj->title), '', 'R');

		return $posy+10;
		
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param  TCPDF		$pdf     			PDF
	 *  @param  Object		$object				Object to show
	 *  @param  Translate	$outputlangs		Object lang for output
	 *  @param  int			$hidefreetext		1=Hide free text
	 *  @return int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'bom_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}

}