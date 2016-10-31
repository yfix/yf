<?php

class yf_send_mail_driver_simple {

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
	function send($params = []) {
		!is_array($params['additional_headers']) && $params['additional_headers'] = [];
		$params['email_from'] && $params['additional_headers']['from'] = 'From: '.$params['email_from'];
		$params['reply_to'] && $params['additional_headers']['reply_to'] = 'Reply-To: '.$params['reply_to'];
		return mail(
			$email_to
			, $subject
			, $text
			, implode("\r\n", $params['additional_headers'])
			, $params['additional_params']
		);
	}
}
