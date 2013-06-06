<?php

/**
* PDF page view handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_pdf_page {

	/**
	* Constructor for PHP 4.x
	* 
	* @private
	*/
	function yf_pdf_page() {
		return $this->__construct();
	}

	/**
	* Constructor for PHP 5.x
	* 
	* @private
	*/
	function __construct() {
		require_once (YF_PATH."libs/html2fpdf/html2fpdf.php");
		$this->pdf_obj =& new html2fpdf();
	}

	/**
	 * Show given text as PDF
	 */
	function go ($text = "", $name = "") {
		main()->NO_GRAPHICS = true;
		if (empty($name)) {
			$name = "page";
		}
		// For now we cut all image tags from source
		// Need to test html2fpdf library about this more carefully
		$text = preg_replace("/<img [^>]+>/ims", "", $text);
        // Process PDF page
		$this->pdf_obj->AddPage();
		$this->pdf_obj->WriteHTML($text);
		$this->pdf_obj->Output($name.'.pdf','D');
	}
}
