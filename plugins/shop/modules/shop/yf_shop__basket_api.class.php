<?php

/**
* Abstraction layer on basket inner representation
* with basic manipulation methods: get/set/del/clean...
*/
class yf_shop__basket_api {

	public $storage_name = "shop_basket_storage";

	/**
	* API Wrapper, allowing to chain itself
	*/
	function _basket_api() {
		return $this;
	}

	/***/
	function get($k, $k2 = false) {
		if (!empty($k2)) {
			return $_SESSION[$storage_name][$k][$k2];
		} else {
			return $_SESSION[$storage_name][$k];
		}
	}

	/***/
	function get_all() {
		return $_SESSION[$storage_name];
	}

	/***/
	function set($k, $v) {
		$_SESSION[$storage_name][$k] = $v;
		return true;
	}

	/***/
	function del($k) {
		unset($_SESSION[$storage_name][$k]);
		return true;
	}

	/***/
	function clean() {
		$_SESSION[$storage_name] = array();
		return true;
	}
	
}