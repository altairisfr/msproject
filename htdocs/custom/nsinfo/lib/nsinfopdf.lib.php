<?php
/**
 * Fonction commune au PDF
 */

/**
 * Fonction retournant la couleur / paramétre globaux
 */
/**
 * @param $refglobal
 * @return false|string[]
 */
function getColorGlobal($refglobal)
{
	$color = !empty(getDolGlobalString($refglobal)) ? getDolGlobalString($refglobal) : 0;
	$color = explode(',', $color);

	return $color;
}

/**
 * @param $pdf
 * @param $color
 * @return void
 */
function setColorTxtDpp($pdf, $color)
{
	$pdf->SetTextColor($color[0], $color[1], $color[2]);
}

/**
 * @param $pdf
 * @param $color
 * @return void
 */
function setColorDrawDpp($pdf, $color)
{
	$pdf->SetDrawColor($color[0], $color[1], $color[2]);
}

/**
 * @param $valconf
 * @param $valeur
 * @return mixed
 */
function getConfGlobalOrVal($valconf, $valeur)
{
	$res = empty(getDolGlobalString($valconf)) ? $valeur : getDolGlobalString($valconf);
	return $res;
}
