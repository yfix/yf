<?php

/**
 * Print view handler
 * 
 * @package		YF
 * @author		Yuri Vysotskiy <yfix.dev@gmail.com>
 * @version		1.0
 * @revision	$Revision$
 */
class yf_print_page {

	/**
	 * Show print version of the page
	 */
	function go ($text = "") {
		main()->NO_GRAPHICS = true;
		$replace = array(
			"text"			=> $text,
			"path_to_tpls"	=> WEB_PATH. tpl()->TPL_PATH,
		);
		echo tpl()->parse("system/common/print_page", $replace);
	}
}
