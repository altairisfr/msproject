<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2021		Christophe Battarel			<christophe.battare@altairis.fr>
 * Copyright (C) 2025		Mélina Joum					<melina.joum@altairis.fr>
 * Copyright (C) 2025		Noé Cendrier				<noe.cendrier@altairis.fr>
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
 */

/**
 * \file    altapdf/class/actions_altapdf.class.php
 * \ingroup altapdf
 * \brief   Altapdf hook overload.
 *
 * Used hooks : formBuilddocOptions, doActions, AddSignature, pdf_build_address, updateSearchRegexToMergeDoc
 */

/**
 * Class ActionsMyModule
 */
class ActionsAltapdf
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;

	function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$langs->load('altapdf@altapdf');

	}

	function formBuilddocOptions($parameters, &$object) {

		global $conf, $langs, $bc;

		$action = GETPOST('action');
		$TContext = explode(':',$parameters['context']);
		if (
				in_array('invoicecard',$TContext)
		        || in_array('invoicesuppliercard',$TContext)
				|| in_array('propalcard',$TContext)
				|| in_array('ordercard',$TContext)
		        || in_array('ordersuppliercard',$TContext)
				|| in_array('invoicereccard',$TContext)
				|| in_array('expeditioncard',$TContext)
			)
	        {
	            $hidePackDetails	= getDolGlobalString('ALTAPDF_FORCE_HIDEPACK_DETAILS') ? 1 : (isset( $_SESSION['altapdf_hidePackDetails'.$object->element][$object->id] ) ?  $_SESSION['altapdf_hidePackDetails'.$object->element][$object->id] : 0);

		     	$out = '<tr class="oddeven">
		     			<td colspan="4" align="right">
		     				<label for="hidePackDetails">'.$langs->trans('HidePackDetails').'</label>
		     				<input type="checkbox" id="hidePackDetails" name="hidePackDetails" value="1" '.(( $hidePackDetails ) ? 'checked="checked"' : '' ).' />
		     			</td>
		     			</tr>';

				$this->resprints = $out;
			}


        return 1;
	}

    function doActions($parameters, $object, &$action)
    {
        global $conf, $db, $langs, $user;

        $langs->load('altapdf@altapdf');

		$element = $object->element ?? '';
		if ($element == 'propal') $element = 'propale';

		if (($element == 'propale' || $element == 'commande') && $action == 'add')
		{
			// raz shipping site si manuel
			if (!empty($_POST['options_manual_shipping_site']))
			{
				$_POST['options_shipping_site'] = '';
			}
		}
	}

	/** Hook to add build the new PDF with customer signature
	 *
	 * @param	array			$parameters	Parameters
	 * @param	CommonObject	$object		Object for action
	 * @param	string			$action		Action string
	 * @return	int
	 */
	function AddSignature($parameters, $object, &$action)
    {
        global $conf, $db, $langs, $user;

		$currentcontext = explode(':', $parameters['context']);

		if (in_array('ajaxonlinesign', $currentcontext)) {
			// We build the new PDF + Redéfinition de l'emplacement de la date + nom + signature
			$error = 0;

			$sourcefile = $parameters['sourcefile'];
			$newpdffilename =  $parameters['newpdffilename'];
			$online_sign_name = GETPOST('onlinesignname');

			$upload_dir = !empty($conf->propal->multidir_output[$object->entity]) ? $conf->propal->multidir_output[$object->entity] : $conf->propal->dir_output;
			$upload_dir .= '/' . dol_sanitizeFileName($object->ref) . '/';

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/" . $date . "_signature.png";

			$pdf = pdf_getInstance();
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($langs));

			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}

			//$pdf->Open();
			$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

			$param = array();
			$param['online_sign_name'] = $online_sign_name;
			$param['pathtoimage'] = $upload_dir . $filename;

			$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
			for ($i = 1; $i < ($pagecount + 1); $i++) {
				try {
					$tppl = $pdf->importPage($i);
					$s = $pdf->getTemplatesize($tppl);
					$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
					$pdf->useTemplate($tppl);
					$propalsignonspecificpage = getDolGlobalInt("PROPAL_SIGNATURE_ON_SPECIFIC_PAGE");
					if ($propalsignonspecificpage < 0) {
						$propalsignonspecificpage = $pagecount - abs($propalsignonspecificpage);
					}

					if (getDolGlobalString("PROPAL_SIGNATURE_ON_ALL_PAGES") || $propalsignonspecificpage == $i) {
						// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
						// TODO Get position of box from PDF template

						if (getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART")) {
							$param['xforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART");
						} else {
							$param['xforimgstart'] = (empty($s['w']) ? 120 : round($s['w'] / 2) + 15);
						}
						if (getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART")) {
							$param['yforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART");
						} else {
							$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 60);
						}
						if (getDolGlobalString("PROPAL_SIGNATURE_WFORIMG")) {
							$param['wforimg'] = getDolGlobalString("PROPAL_SIGNATURE_WFORIMG");
						} else {
							$param['wforimg'] = $s['w'] - 20 - $param['xforimgstart'];
						}

						// dolPrintSignatureImage($pdf, $langs, $param);

						// Redéfinition de l'emplacement de la date + nom + signature
						$default_font_size = pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
						$default_font = pdf_getPDFFont($langs);	// Must be
						$xforimgstart = $param['xforimgstart'];
						$yforimgstart = $param['yforimgstart'];
						$wforimg = $param['wforimg'];

						$yforimgstart = 217;
						$xforimgstart = 130;
						$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4) - 4);
						$pdf->SetFont($default_font, '', $default_font_size - 1);
						$pdf->SetTextColor(80, 80, 80);
						$date_sign = dol_print_date(dol_now(), "day", false, $langs, true);
						$date_sign .= "\n".$param['online_sign_name'];
						$pdf->MultiCell($wforimg, 4, $date_sign, 0, 'L');

						$yforimgstart = $param['yforimgstart'];
						$xforimgstart = $param['xforimgstart'];
						$pdf->Image($param['pathtoimage'], $xforimgstart, $yforimgstart, $wforimg, round($wforimg / 4));
					}
				} catch (Exception $e) {
					dol_syslog("Error when manipulating the PDF " . $sourcefile . " by onlineSign: " . $e->getMessage(), LOG_ERR);
					$response = $e->getMessage();
					$error++;
				}
			}

			if (!getDolGlobalString("PROPAL_SIGNATURE_ON_ALL_PAGES") && !getDolGlobalInt("PROPAL_SIGNATURE_ON_SPECIFIC_PAGE")) {
				// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
				// TODO Get position of box from PDF template

				if (getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART")) {
							$param['xforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART");
				} else {
					$param['xforimgstart'] = (empty($s['w']) ? 120 : round($s['w'] / 2) + 15);
				}
				if (getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART")) {
					$param['yforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART");
				} else {
					$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 60);
				}
				if (getDolGlobalString("PROPAL_SIGNATURE_WFORIMG")) {
					$param['wforimg'] = getDolGlobalString("PROPAL_SIGNATURE_WFORIMG");
				} else {
					$param['wforimg'] = $s['w'] - 20 - $param['xforimgstart'];
				}

				// dolPrintSignatureImage($pdf, $langs, $param);

				// Redéfinition de l'emplacement de la date + nom + signature
				$default_font_size = pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
				$default_font = pdf_getPDFFont($langs);	// Must be
				$xforimgstart = $param['xforimgstart'];
				$yforimgstart = $param['yforimgstart'];
				$wforimg = $param['wforimg'];

				$yforimgstart = 217;
				$xforimgstart = 130;
				$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4) - 4);
				$pdf->SetFont($default_font, '', $default_font_size - 1);
				$pdf->SetTextColor(80, 80, 80);
				$date_sign = dol_print_date(dol_now(), "day", false, $langs, true);
				$date_sign .= "\n".$param['online_sign_name'];
				$pdf->MultiCell($wforimg, 4, $date_sign, 0, 'L');

				$yforimgstart = $param['yforimgstart'];
				$xforimgstart = $param['xforimgstart'];
				$pdf->Image($param['pathtoimage'], $xforimgstart, $yforimgstart, $wforimg, round($wforimg / 4));
			}

			//$pdf->Close();
			$pdf->Output($newpdffilename, "F");

			// Index the new file and update the last_main_doc property of object.
			$object->indexFile($newpdffilename, 1);
			return 1;
		}
	}

	/**
	 *   Retourne les adresses avec le contact émetteur du responsable suivi commande ou créateur ou validation ou par défaut société
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return	string					    		        String with full address
	 */
	function pdf_build_address($parameters, $object, $action)
	{
		global $conf, $db;

		$currentcontext = explode(':', $parameters['context']);

		$stringaddress = '';

		if (in_array('ordersuppliercard', $currentcontext)) {
			$mode = $parameters['mode'];
			$sourcecompany = $parameters['sourcecompany'];
			$outputlangs = $parameters['outputlangs'];
			$targetcompany = $parameters['targetcompany'];
			$usecontact = $parameters['usecontact'];
			$targetcontact= $parameters['targetcontact'];

			if ($mode == 'source') {
				$withCountry = 0;
				if (isset($targetcompany->country_code) && !empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
					$withCountry = 1;
				}

				$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

				if (!getDolGlobalString('MAIN_PDF_DISABLESOURCEDETAILS')) {

					// Début spécif : Contact émetteur
					include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
					$supplier_order = new CommandeFournisseur($db);
					$supplier_order->fetch(GETPOST('id'));

					include_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
					$arrayidcontact = $supplier_order->getIdContact('internal', 'SALESREPFOLL');
					if (count($arrayidcontact) > 0) {
						// Contact responsable suivi commande fournisseur
						$supplier_order->fetch_user($arrayidcontact[0]);
						// Phone
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($supplier_order->user->office_phone);
						// EMail
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($supplier_order->user->email);
					} else {
						// Contact validation de la commande fournisseur
						$supplier_order->fetch_user($supplier_order->user_validation_id);
						// Phone
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($supplier_order->user->office_phone);
						// EMail
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($supplier_order->user->email);
					}

					// // Contact créateur de la commande fournisseur
					// $supplier_order->fetch_user($supplier_order->user_author_id);
					// if (!empty($supplier_order->user->office_phone) && !empty($supplier_order->user->email)) {
					// 	// Phone
					// 	$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($supplier_order->user->office_phone);
					// 	// EMail
					// 	$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($supplier_order->user->email);
					// }
					// // Contact société (par défaut)
					// // Phone
					// if ($sourcecompany->phone) {
					// 	$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
					// }
					// // EMail
					// if ($sourcecompany->email) {
					// 	$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
					// }

					// Fin spécif : Contact émetteur

					// Web
					if ($sourcecompany->url) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
					}
				}
				// Intra VAT
				if (getDolGlobalString('MAIN_TVAINTRA_IN_SOURCE_ADDRESS')) {
					if ($sourcecompany->tva_intra) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
					}
				}
				// Professional Ids
				$reg = array();
				if (getDolGlobalString('MAIN_PROFID1_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof1)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof1);
				}
				if (getDolGlobalString('MAIN_PROFID2_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof2)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof2);
				}
				if (getDolGlobalString('MAIN_PROFID3_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof3)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof3);
				}
				if (getDolGlobalString('MAIN_PROFID4_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof4)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof4);
				}
				if (getDolGlobalString('MAIN_PROFID5_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof5)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof5);
				}
				if (getDolGlobalString('MAIN_PROFID6_IN_SOURCE_ADDRESS') && !empty($sourcecompany->idprof6)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof6);
				}
				if (getDolGlobalString('PDF_ADD_MORE_AFTER_SOURCE_ADDRESS')) {
					$stringaddress .= ($stringaddress ? "\n" : '') . getDolGlobalString('PDF_ADD_MORE_AFTER_SOURCE_ADDRESS');
				}
			}

			if ($mode == 'target' || preg_match('/targetwithdetails/', $mode)) {
				if ($usecontact) {
					if (is_object($targetcontact)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs, 1));

						if (!empty($targetcontact->address)) {
							$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
						} else {
							$companytouseforaddress = $targetcompany;

							// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
							if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {
								$targetcontact->fetch_thirdparty();
								$companytouseforaddress = $targetcontact->thirdparty;
							}

							$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress))."\n";
						}
						// Country
						if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
							$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code));
						} elseif (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
							$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
						}

						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || preg_match('/targetwithdetails/', $mode)) {
							// Phone
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
								if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
								}
								if (!empty($targetcontact->phone_pro)) {
									$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
								}
								if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) {
									$stringaddress .= " / ";
								}
								if (!empty($targetcontact->phone_mobile)) {
									$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
								}
							}
							// Fax
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
								if ($targetcontact->fax) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
								}
							}
							// EMail
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
								if ($targetcontact->email) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
								}
							}
							// Web
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
								if ($targetcontact->url) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
								}
							}
						}
					}
				} else {
					if (is_object($targetcompany)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset(dol_format_address($targetcompany));
						// Country
						if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
							$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code));
						} else {
							$stringaddress .= ($stringaddress ? "\n" : '');
						}

						if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || preg_match('/targetwithdetails/', $mode)) {
							// Phone
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_phone/', $mode)) {
								if (!empty($targetcompany->phone)) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Phone").": ";
								}
								if (!empty($targetcompany->phone)) {
									$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone);
								}
							}
							// Fax
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_fax/', $mode)) {
								if ($targetcompany->fax) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
								}
							}
							// EMail
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_email/', $mode)) {
								if ($targetcompany->email) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
								}
							}
							// Web
							if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS') || $mode == 'targetwithdetails' || preg_match('/targetwithdetails_url/', $mode)) {
								if ($targetcompany->url) {
									$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
								}
							}
						}
					}
				}

				// Intra VAT
				if (!getDolGlobalString('MAIN_TVAINTRA_NOT_IN_ADDRESS')) {
					if ($usecontact && is_object($targetcontact) && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
						$targetcontact->fetch_thirdparty();
						if (!empty($targetcontact->thirdparty->id) && $targetcontact->thirdparty->tva_intra) {
							$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("VATIntraShort") . ': ' . $outputlangs->convToOutputCharset($targetcontact->thirdparty->tva_intra);
						}
					} elseif (!empty($targetcompany->tva_intra)) {
						$stringaddress .= ($stringaddress ? "\n" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
					}
				}

				// Legal form
				if (getDolGlobalString('MAIN_LEGALFORM_IN_ADDRESS') && !empty($targetcompany->forme_juridique_code)) {
					$tmp = getFormeJuridiqueLabel($targetcompany->forme_juridique_code);
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp;
				}

				// Professional Ids
				if (getDolGlobalString('MAIN_PROFID1_IN_ADDRESS') && !empty($targetcompany->idprof1)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId1", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
				}
				if (getDolGlobalString('MAIN_PROFID2_IN_ADDRESS') && !empty($targetcompany->idprof2)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId2", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
				}
				if (getDolGlobalString('MAIN_PROFID3_IN_ADDRESS') && !empty($targetcompany->idprof3)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId3", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
				}
				if (getDolGlobalString('MAIN_PROFID4_IN_ADDRESS') && !empty($targetcompany->idprof4)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId4", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
				}
				if (getDolGlobalString('MAIN_PROFID5_IN_ADDRESS') && !empty($targetcompany->idprof5)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId5", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof5);
				}
				if (getDolGlobalString('MAIN_PROFID6_IN_ADDRESS') && !empty($targetcompany->idprof6)) {
					$tmp = $outputlangs->transcountrynoentities("ProfId6", $targetcompany->country_code);
					if (preg_match('/\((.+)\)/', $tmp, $reg)) {
						$tmp = $reg[1];
					}
					$stringaddress .= ($stringaddress ? "\n" : '').$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof6);
				}

				// Public note
				if (getDolGlobalString('MAIN_PUBLIC_NOTE_IN_ADDRESS')) {
					if ($mode == 'source' && !empty($sourcecompany->note_public)) {
						$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($sourcecompany->note_public);
					}
					if (($mode == 'target' || preg_match('/targetwithdetails/', $mode)) && !empty($targetcompany->note_public)) {
						$stringaddress .= ($stringaddress ? "\n" : '').dol_string_nohtmltag($targetcompany->note_public);
					}
				}
			}
		}
		$this->resprints = $stringaddress;
		// return should be not empty if we have something in resprints
		return (!empty($this->resprints));
	}

	/**
	 * Replace name pattern for PDFs to merge in list massaction (in Dolibarr 22+)
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject	$object         Object for action
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return 	int								< 0 KO, 0 OK, > 0 replaces source elements
	 */

	public function updateSearchRegexToMergeDoc($parameters, $object, $action, $hookmanager)
	{

		if (in_array($parameters['currentcontext'], array('orderlist', 'invoicelist', 'propallist', 'productreturnlist', 'supplierorderlist', 'shipmentlist'))) {

			if (!empty(getDolGlobalString('ALTAPDF_MANUAL_PREFIX_PDF'))) {
				$prefix = getDolGlobalString('ALTAPDF_MANUAL_PREFIX_PDF');
			} elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 1) {
				$prefix = '';
			} elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 2) {
				$prefix = str_replace(' ', '', $_SESSION["dol_company"]).'_';
			} elseif (getDolGlobalInt('ALTAPDF_PREFIX_PDF') == 3) {
				$prefix = 'altairis_';
			}

			foreach ($parameters["listofobjectref"] as $tmppdf) {
				$parameters["arrayofinclusion"][] = '^'.$prefix.preg_quote(dol_sanitizeFileName($tmppdf), '/').'\.pdf$';
			}
			foreach ($parameters["listofobjectref"] as $tmppdf) {
				$parameters["arrayofinclusion"][] = '^'.$prefix.preg_quote(dol_sanitizeFileName($tmppdf), '/').'_[a-zA-Z0-9\-\_\'\&\.]+\.pdf$'; // To include PDF generated from ODX files
			}
			return 1;
		}
		return 0;
	}
}
