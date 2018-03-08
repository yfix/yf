<?php

load('mail_driver', '', 'classes/mail/');
class yf_mail_driver_sendinblue extends yf_mail_driver {

	/** @var string The sendinblue API key. */
	public $key;

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
	* https://github.com/mailin-api/mailin-api-php/tree/master/src/Sendinblue
	*/
	function send(array $params = [], &$error_message = '') {
		require_php_lib('sendinblue');

		$error_message = null;
		try {
			$sb = new Sendinblue\Mailin('https://api.sendinblue.com/v2.0', $this->key);
			$data = [
				'to' => [$params['email_to'] => $params['name_to']],
				'from' => [$params['email_from'], $params['name_from']],
#				'replyto' => [$params['reply_to']],
				'subject' => $params['subject'],
				'text' => $params['text'],
				'html' => $params['html'],
#				'attachment' => [], // Provide the absolute URL of the attachment/
			];
			$result = $sb->send_email($data);
			if ($result['code'] != 'success') {
				$error_message = $result['message'];
			}
		} catch(Exception $e) {
			$error_message = 'A sendinblue error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
		}
		if (@$error_message && DEBUG_MODE && $this->PARENT->MAIL_DEBUG_ERROR) {
			trigger_error($error_message, E_USER_WARNING);
		}
		if (is_callable($params['on_after_send'])) {
			$callback = $params['on_after_send'];
			$callback($mail, $params, $result, $error_message, $this->PARENT);
		}
		$this->PARENT->_last_error_message = $error_message;
		return $result && $result['code'] == 'success' && !$error_message ? true : false;
	}
}
