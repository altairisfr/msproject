<?php
/* Copyright (C) 2021      Nicolas Silobre      <nicolas.silobre@it-bs.fr>



*/

require_once DOL_DOCUMENT_ROOT.'/core/modules/bom/modules_bom.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php';

// dol_include_once('/bompdf/lib/pi_barcode.php');


/**
 *	Class to generate PDF BOM 
 */

class pdf_nomenbompdfbc extends ModelePDFBom
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
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bom","bompdf@bompdf"));

		$this->db = $db;
		$this->name = "BOM BarCode";
		$this->description = $langs->trans('BomPdfBCDescription');

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
		$this->posxdesc = (!empty($conf->global->BOM_LARG_REF) ? $conf->global->BOM_LARG_REF : 20) + $this->marge_gauche + 1;
		$this->posxqty = 135;
		$this->posxunit = 160;
		$this->poxcoutunit = 170;

		$this->xentetelib = $this->page_largeur - $this->marge_droite - 130;
		$this->xentetedata = $this->page_largeur - $this->marge_droite - 80;

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
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "bom", "mrp"));

		
		$nblines = count($object->lines);
		
		if ($conf->bom->dir_output) 
		{
			// Definition of $dir and $file
			if ($object->specimen) 
			{
				$dir = $conf->bom->dir_output;
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->bom->dir_output."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			if (!file_exists($dir)) 
			{
				if (dol_mkdir($dir) < 0) 
				{
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) 
			{
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) 
				{
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
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) 
				{
					$heightforfooter += 6;
				}
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) 
				{
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
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("CommercialAsk")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) 
				{
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) 
				{
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$coutt = $object->calculateCosts();
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $coutt->total_cost);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = $pdf->GetY();
				$tab_top_newpage = $pdf->GetY()+10;



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
				$cout = 0;
				
				$objligne = new BOMLine($db);
				

				for ($i = 0; $i < $nblines; $i++) 
				{
					$objproduct = new Product($db);
					$C2D = 0;
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;

					// Description of product line
					$curX = $this->posxdesc - 1;

					$pdf->startTransaction();

					$objproduct->fetch($object->lines[$i]->fk_product);
					$pdf->SetXY($this->posxdesc, $curY);
					$pdf->MultiCell(($this->posxqty - $this->posxdesc-1), 3, $objproduct->label,'','L');

					$pageposafter = $pdf->getPage();

					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
						$pdf->SetXY($this->posxdesc, $curY);
						$pdf->MultiCell(($this->posxqty - $this->posxdesc-1), 3, $objproduct->label,'','L');

						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break

							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}
					} 
					else // No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription = $pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// Ref et BarCode
					$pdf->SetXY($this->posxref, $curY);
					$res = $objproduct->fetch_barcode();
					if ($res > 0 && !empty($objproduct->barcode)) {
						$code1D = array("EAN13", "EAN8", "ISBN", "C39", "C128", "UPC", "CBR");

						if (in_array($objproduct->barcode_type_code, $code1D)) $pdf->write1DBarcode($objproduct->barcode, $objproduct->barcode_type_code, $this->posxref, $curY, ($this->posxdesc - $this->posxref -2), 4);
						else {
							$C2D = 1;
							
							$pdf->write2DBarcode($objproduct->barcode, $objproduct->barcode_type_code, $this->posxref, $curY, ($this->posxdesc - $this->posxref), 10);
							
						}
					}
					else $pdf->MultiCell(($this->posxdesc - $this->posxref), 3, $objproduct->ref, '','L');
					$curref = $pdf->GetY();
					//Qty
					$pdf->SetXY($this->posxqty, $curY);
					$nbdecimal = (!empty($conf->global->BOM_NBDECIMALE) ? $conf->global->BOM_NBDECIMALE : 5);
					$pdf->MultiCell($this->posxunit - $this->posxqty-1, 3, number_format($object->lines[$i]->qty, $nbdecimal),'','R');

					//unite
					$pdf->SetXY($this->posxunit, $curY);
					$unit->fetch($objproduct->fk_unit);
					$pdf->MultiCell($this->poxcoutunit - $this->posxunit, 3, $unit->short_label,'', 'L');

					$posYAfterDescription = $pdf->GetY();
					// $nexY = $curref;
					// $nexY = $pdf->GetY();
					if ($curref < $posyafter)
					{
						$curref = $posyafter;
					}
					if ($nexY < $curref)
					{
						$nexY = $curref;
					}
					if ($C2D == 1) {
						$nexY += 6;
					}
					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) 
					{

						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1', 'color'=>array(80, 80, 80)));
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY += 2;
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 1, 1);
						} 
						else 
						{
							$tab_top += 7;
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
							
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) 
						{
							$this->_pagehead($pdf, $object, 0, $outputlangs);
							$tab_top = $pdf->GetY();
						}
					}
						// $i++;
				}
				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top+5, $this->page_hauteur - $tab_top - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

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

		$pdf->SetTextColor(0, 0, 0);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetXY($this->posxdesc , $tab_top + 0.5);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');

		$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5); // line takes a position y in 2nd parameter and 4th parameter

		$pdf->SetXY($this->posxref - 1, $tab_top + 0.5);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("BarCode"), '', 'L');
		$pdf->line($this->posxdesc, $tab_top, $this->posxdesc, $tab_top + $tab_height);

		$pdf->SetXY($this->posxqty - 1, $tab_top + 0.5);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Qty"), '', 'L');
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty, $tab_top + $tab_height);

		$pdf->SetXY($this->posxunit - 1, $tab_top + 0.5);
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
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs,$coutt='')
	{
		global $conf,$langs,$db;

		$outputlangs->loadLangs(array("main", "other", "mrp", "companies", "docperso@docperso"));

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
				$pdf->Image($logo, $this->marge_gauche, $posy, 60, 0); // width=0 (auto)
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
		$title = $outputlangs->transnoentities("BOM");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(0, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy += 5;
		$pdf->SetFont('', '', $default_font_size - 2);
		$hautcadrey = $posy;
		$hautcadrex = $this->xentetelib;

		$posx += 1;
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Label"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($object->label), '', 'L');
		
		$posy += 4;
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Type"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($object->bomtype == 0 ? "Manufacturing" : "Disassemble"), '', 'L');
		
		$posy += 4;
		$objprd = new Product($db);
		$objprd->fetch($object->fk_product);
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Product"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, $objprd->ref." ".$outputlangs->convToOutputCharset($objprd->label), '', 'L');
		$posy = $pdf->GetY();

		$pdf->SetXY($this->xentetelib, $posy);
		$libqte = $outputlangs->transnoentities("Quantity").(!empty($conf->global->BOM_COUTTOTAL) ? " / ".$outputlangs->transnoentities("TotalCost") : '');
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $libqte, '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$qtycct = $outputlangs->convToOutputCharset($object->qty).(!empty($conf->global->BOM_COUTTOTAL) ? " / ".price($object->total_cost) : '');
		$pdf->MultiCell(0, 4, $qtycct, '', 'L');

		

		$posy = $pdf->GetY()+1;
		$objwareh = new Entrepot($db);
		$objwareh->fetch($object->fk_warehouse);
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("WarehouseForProduction"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($objwareh->ref), '', 'L');

		$posy += 4;
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("EstimatedDuration"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, gmdate("H:i",$object->duration), '', 'L');

		$posy += 4;
		$pdf->line($this->xentetelib, $posy,  $this->page_largeur - $this->marge_droite, $posy);
		$pdf->SetXY($this->xentetelib, $posy);
		$pdf->MultiCell($this->xentetelib - $this->xentetedata, 4, $outputlangs->transnoentities("Description"), '', 'L');
		$pdf->SetXY($this->xentetedata, $posy);
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($object->description), '', 'L');
		
		$posx = $this->page_largeur - $this->marge_droite - 130;
		$posy = $pdf->GetY();
		$pdf->SetXY($posx, $posy);
		$this->printRect($pdf, $hautcadrex, $hautcadrey, 130, $posy-$hautcadrey); // Rect takes a length in 3rd parameter and 4th parameter
		$pdf->line($posx + 50, $hautcadrey, $posx + 50, $posy);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$posy = $pdf->GetY();
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