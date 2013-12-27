<?php

/**
* PDF page view handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_pdf_page {

	/**
	*/
	function __construct() {
		require_once (YF_PATH."libs/mPDF/mpdf.php");
		$this->pdf_obj = new mPDF('utf-8', 'A4','10');
	}

	/**
	 * Show given text as PDF
	 */
	function go ($text = "", $name = "") {
		main()->NO_GRAPHICS = true;
		if (empty($name)) {
			$name = "page";
		}
			
		$this->pdf_obj->charset_in = 'cp1251';
		$this->pdf_obj->WriteHTML($text, 2); 
		$this->pdf_obj->Output($name.'.pdf', 'I');
	}
}
