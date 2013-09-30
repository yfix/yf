<?php

/**
* Show empty page (useful for popup windows, etc)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_empty_page {

	/**
	* Show empty page (useful for popup windows, etc)
	*/
	function _show ($text = '', $params = array()) {
		$output = '';
		main()->NO_GRAPHICS = true;
		$CSS_FILE = !empty($params['css_file']) ? $params['css_file'] : 'style.css';
		$replace = array(
			'css'			=> '<link rel="stylesheet" type="text/css" href="'.WEB_PATH. tpl()->TPL_PATH. $CSS_FILE.'">',
			'text'			=> $text,
			'title'			=> $params['title'],
			'close_button'	=> (int)((bool)$params['close_button']),
			'full_width'	=> (int)((bool)$params['full_width']),
		);
		$output .= tpl()->parse('system/empty_page', $replace);
		if (DEBUG_MODE && common()->EMPTY_PAGE_DEBUG_INFO && !$params['no_debug']) {
			$output .= common()->_show_execution_time();
			$output .= common()->show_debug_info();
		}
		$output = tpl()->_apply_output_filters($output);
		main()->_send_main_headers(strlen($output));
		echo $output;
	}
}
