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
	function _go ($location, $rewrite = true, $redirect_type = 'js', $text = '', $ttl = 3, $params = array()) {
		if (is_array($location)) {
			$params += $location;
			$rewrite = isset($params['rewrite']) ? $params['rewrite'] : $rewrite;
			$redirect_type = isset($params['redirect_type']) ? $params['redirect_type'] : $redirect_type;
			$text = isset($params['text']) ? $params['text'] : $text;
			$ttl = isset($params['ttl']) ? $params['ttl'] : $ttl;
			$location = $params['location'];
		}
		$form_method = in_array(strtoupper($params['form_method']), array('GET','POST')) ? strtoupper($params['form_method']) : 'GET';
		if ($GLOBALS['no_redirect']) {
			return $text;
		}
		if (main()->_IS_REDIRECTING) {
			return false;
		}
		main()->NO_GRAPHICS = true;
		main()->_IS_REDIRECTING = true;
		if (empty($location)) {
			$location = './?object='.$_GET['object']
				. ($_GET['action'] != 'show' ? '&action='.$_GET['action'] : '')
				. ($_GET['id'] ? '&id='.$_GET['id'] : '')
				. ($_GET['page'] ? '&page='.$_GET['page'] : '');
		}
		if ($rewrite && tpl()->REWRITE_MODE && MAIN_TYPE_USER) {
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
			$hidden_fields = '';
			if ($form_method == 'GET') {
				$query = parse_url($location, PHP_URL_QUERY);
				$fields = array();
				if (strlen($query)) {
					parse_str($query, $fields);
				}
				if ($fields) {
					$form = form($fields, array('no_form' => 1));
					foreach ((array)$fields as $k => $v) {
						$form->hidden($k);
					}
					$hidden_fields = $form;
				}
			}
			$body .= tpl()->parse('system/redirect', array(
				'mode'			=> 'debug',
				'normal_mode'	=> $redirect_type,
				'rewrite'		=> intval((bool)$rewrite),
				'location'		=> $location,
				'text'			=> $text,
				'ttl'			=> intval($ttl),
				'form_method'	=> $form_method,
				'hidden_fields'	=> $hidden_fields,
			));
			$body .= '<pre><small>'.htmlspecialchars(main()->trace_string()).'</small></pre>';
			return print common()->show_empty_page($body, array('full_width' => 1));
		}
		if (empty($redirect_type) || !in_array($redirect_type, $this->AVAIL_TYPES)) {
			$redirect_type = 'http';
		}
		if ($this->FORCE_TYPE/* && empty($text)*/) {
			$redirect_type = $this->FORCE_TYPE;
		}
		if ($redirect_type == 'js') {
			$body = $this->_redirect_js($location, $text, $ttl, $params);
		} elseif ($redirect_type == 'html') {
			$body = $this->_redirect_html($location, $text, $ttl, $params);
		} elseif ($redirect_type == 'http') {
			$body = $this->_redirect_http($location, $text, $ttl, $params);
		}
		echo $this->USE_DESIGN && !empty($body) ? common()->show_empty_page($body, array('full_width' => 1)) : $body;
	}

	/**
	* JavaScript redirect method (with 'degrade gracefully' feature)
	*/
	function _redirect_js ($location, $text = '', $ttl = 0, $params = array()) {
		$replace = array(
			'mode'			=> 'js',
			'location'		=> $location,
			'text'			=> $text,
			'ttl'			=> intval($ttl),
			'html_redirect'	=> $this->_redirect_html($location, $text, $ttl),
			'js_show_text'	=> (int)((bool)$this->JS_SHOW_TEXT),
			'form_method'	=> $params['form_method'],
		);
		return tpl()->parse('system/redirect', $replace);
	}

	/**
	* HTML redirect method
	*/
	function _redirect_html ($location, $text = '', $ttl = 3, $params = array()) {
		$replace = array(
			'mode'			=> 'html',
			'location'		=> $location,
			'text'			=> $text,
			'ttl'			=> intval($ttl),
			'form_method'	=> $params['form_method'],
		);
		return tpl()->parse('system/redirect', $replace);
	}

	/**
	* HTTP redirect method (using response headers)
	*/
	function _redirect_http ($location, $text = '', $ttl = 3, $params = array()) {
		header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 302 Found');
		header('Location: '.$location);
		return '';
	}
}
