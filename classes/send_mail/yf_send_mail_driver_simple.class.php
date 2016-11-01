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
		!is_array($params['headers']) && $params['headers'] = [];
		$params['email_from'] && $params['headers']['from'] = 'From: '.$params['email_from'];
		$params['reply_to'] && $params['headers']['reply_to'] = 'Reply-To: '.$params['reply_to'];
		return mail(
			$params['email_to']
			, $params['subject']
			, $params['text']
			, implode("\r\n", $params['headers'])
			, implode('', $params['mta_params'])
		);
	}
}
