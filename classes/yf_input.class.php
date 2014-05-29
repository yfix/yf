<?php

/**
* Core input
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_input {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Helper to get/set GET vars
	*/
	function get($key = null, $val = null) {
		if (!is_null($val)) {
			$_GET[$key] = $val;
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_GET : $_GET[$key];
	}

	/**
	* Helper to get/set POST vars
	*/
	function post($key = null, $val = null) {
		if (!is_null($val)) {
			$_POST[$key] = $val;
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_POST : $_POST[$key];
	}

	/**
	* Helper to get/set SESSION vars
	*/
	function session($key = null, $val = null) {
		if (!is_null($val)) {
			$_SESSION[$key] = $val;
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_SESSION : $_SESSION[$key];
	}

	/**
	* Helper to get/set SERVER vars
	*/
	function server($key = null, $val = null) {
		if (!is_null($val)) {
			$_SERVER[$key] = $val;
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_SERVER : $_SERVER[$key];
	}

	/**
	* Helper to get/set COOKIE vars
	*/
	function cookie($key = null, $val = null) {
		if (!is_null($val)) {
# TODO: check and use main() settings for cookies
			setcookie($key, $val);
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_COOKIE : $_COOKIE[$key];
	}

	/**
	* Helper to get/set ENV vars
	*/
	function env($key = null, $val = null) {
		if (!is_null($val)) {
			$_ENV[$key] = $val;
		}
		if (DEBUG_MODE && function_exists('debug')) {
			debug('input_'.__FUNCTION__.'[]', array(
				'name'	=> $key,
				'val'	=> $val,
				'op'	=> $val !== null ? 'set' : 'get',
				'trace'	=> main()->trace_string(),
			));
		}
		return $key === null ? $_ENV : $_ENV[$key];
	}

	/**
	* Checks whether current page was requested with POST method
	*/
	function is_post() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	* Checks whether current page was requested with AJAX
	*/
	function is_ajax() {
		return (bool)conf('IS_AJAX');
	}

	/**
	* Checks whether current page was requested from console
	*/
	function is_console() {
		return (bool)main()->CONSOLE_MODE;
	}

	/**
	* Checks whether current page is a redirect
	*/
	function is_redirect() {
		return (bool)main()->_IS_REDIRECTING;
	}
}
