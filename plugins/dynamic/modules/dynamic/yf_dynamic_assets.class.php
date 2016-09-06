<?php

/**
*/
class yf_dynamic_assets {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function __construct() {
		$this->_parent = module('dynamic');
	}
	
	/**
	* Display dynamic|on-the-fly asset content (CSS|JS)
	*/
	function asset ($type = '') {
		no_graphics(true);
		$name = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['id']));
		$type = preg_replace('~[^a-z0-9_-]+~ims', '', trim($type ?: $_GET['page']));
		if (!strlen($name) || !strlen($type) || !in_array($type, ['css','js','jquery','ng'])) {
			_404();
			exit();
		}
	}

	/**
	*/
	function asset_css () {
		return $this->asset('css');
	}

	/**
	*/
	function asset_js () {
		return $this->asset('js');
	}

	/**
	*/
	function asset_jquery () {
		return $this->asset('jquery');
	}

	/**
	*/
	function asset_ng () {
		return $this->asset('ng');
	}
}
