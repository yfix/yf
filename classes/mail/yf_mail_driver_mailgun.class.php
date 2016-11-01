<?php

load('mail_driver', 'framework', 'classes/mail/');
class yf_mail_driver_mailgun extends yf_mail_driver {

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
	function send(array $params = [], &$error_message = '') {
// TODO
	}
}
