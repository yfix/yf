<?php

load('search_driver', 'framework', 'classes/mail/');
class yf_search_driver_solr extends yf_search_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _init() {
		$this->PARENT = _class('send_mail');
	}

	/**
	*/
	function search(array $params = [], &$error_message = '') {
// TODO
	}
}
