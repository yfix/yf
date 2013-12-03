<?php

/**
* Redirects handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_redirect {

	/** @var array @conf_skip Available redirect types */
	public $AVAIL_TYPES = array(
		'html',
		'js',
		'http',
	);
	/** @var bool */
	public $USE_DESIGN		= false;
	/** @var bool */
	public $JS_SHOW_TEXT	= false;
	/** @var string Force using only this method (if text is empty), leave blank to disable */
	public $FORCE_TYPE		= 'http';

	/**
	* Common redirect method
	*/
	function _go ($location, $rewrite = true, $redirect_type = 'js', $text = '', $ttl = 3) {
		if ($GLOBALS['no_redirect']) {
			return $text;
		}
		if (main()->_IS_REDIRECTING) {
			return false;
		}
		main()->NO_GRAPHICS = true;
		main()->_IS_REDIRECTING = true;
		if (empty($location)) {
			$location = './?object='.$_GET['object'];
		}
		if (tpl()->REWRITE_MODE && $rewrite && MAIN_TYPE_USER) {
			$location = module('rewrite')->_rewrite_replace_links($location, true);
		}
		$location = str_replace('??', '?', $location);
		if ($location == './?') {
			$location = './';
		}
		// Exec hook before redirecting
		$hook_name = '_on_before_redirect';
		$obj = module($_GET['object']);
		if (method_exists($obj, $hook_name)) {
			$obj->$hook_name($text);
		}
		if (DEBUG_MODE) {
			$body .= tpl()->parse('system/redirect', array(
				'mode'			=> 'debug',
				'normal_mode'	=> $redirect_type,
				'rewrite'		=> intval((bool)$rewrite),
				'location'		=> $location,
				'text'			=> $text,
				'ttl'			=> intval($ttl),
			));
			$body .= '<pre><small>'.htmlspecialchars(main()->trace_string()).'</small></pre>';
			return print common()->show_empty_page($body, array('full_width' => 1/*, 'no_debug' => 1*/));
		}
		if (empty($redirect_type) || !in_array($redirect_type, $this->AVAIL_TYPES)) {
			$redirect_type = 'http';
		}
		if ($this->FORCE_TYPE && empty($text)) {
			$redirect_type = $this->FORCE_TYPE;
		}
		if ($redirect_type == 'js') {
			$body = $this->_redirect_js($location, $text, $ttl);
		} elseif ($redirect_type == 'html') {
			$body = $this->_redirect_html($location, $text, $ttl);
		} elseif ($redirect_type == 'http') {
			$body = $this->_redirect_http($location, $text, $ttl);
		}
		echo $this->USE_DESIGN && !empty($body) ? common()->show_empty_page($body, array('full_width' => 1)) : $body;
	}

	/**
	* JavaScript redirect method (with 'degrade gracefully' feature)
	*/
	function _redirect_js ($location, $text = '', $ttl = 0) {
		$replace = array(
			'mode'			=> 'js',
			'location'		=> $location,
			'text'			=> $text,
			'ttl'			=> intval($ttl),
			'html_redirect'	=> $this->_redirect_html($location, $text, $ttl),
			'js_show_text'	=> (int)((bool)$this->JS_SHOW_TEXT),
		);
		return tpl()->parse('system/redirect', $replace);
	}

	/**
	* HTML redirect method
	*/
	function _redirect_html ($location, $text = '', $ttl = 3) {
		$replace = array(
			'mode'		=> 'html',
			'location'	=> $location,
			'text'		=> $text,
			'ttl'		=> intval($ttl),
		);
		return tpl()->parse('system/redirect', $replace);
	}

	/**
	* HTTP redirect method (using response headers)
	*/
	function _redirect_http ($location, $text = '', $ttl = 3) {
		header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 302 Found');
		header('Location: '.$location);
		return '';
	}
}
