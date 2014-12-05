<?php

class yf_core_js {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Direct call to object as to string is not allowed, return empty string instead
	*/
	function __toString() {
		return '';
	}

	/**
	*/
	public function add($content, $content_type_hint = 'auto', $params = array()) {
		return _class('assets')->add($content, 'js', $content_type_hint, $params);
	}

	/**
	*/
	public function add_url($content, $params = array()) {
		return $this->add($content, 'url', $params);
	}

	/**
	*/
	public function add_file($content, $params = array()) {
		return $this->add($content, 'file', $params);
	}

	/**
	*/
	public function add_inline($content, $params = array()) {
		return $this->add($content, 'inline', $params);
	}

	/**
	*/
	public function add_raw($content, $params = array()) {
		return $this->add($content, 'raw', $params);
	}

	/**
	*/
	public function add_asset($content, $params = array()) {
		return $this->add($content, 'asset', $params);
	}

	/**
	* Output JS content
	*/
	public function show($params = array()) {
		return _class('assets')->show_js($params);
	}

	/**
	* Alias
	*/
	public function show_js($params = array()) {
		return $this->show($params);
	}
}
