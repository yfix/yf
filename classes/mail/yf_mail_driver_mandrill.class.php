<?php

load('mail_driver', 'framework', 'classes/mail/');
class yf_mail_driver_mandrill extends yf_mail_driver {

	/** @var string The Mandrill API key. */
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

#
#	'html' => $params['html'],
#	'text' => $params['text'],
#	'subject' => $params['subject'],
#	'from_email' => $params['email_from'],
#	'from_name' => $params['name_from'],
#	'headers' => ['Reply-To' => $params['reply_to'] ?: $params['from']],
#	'to' => [
#		[
#			'email' => $params['email_to'],
#			'name' => $params['name_to'],
#			'type' => 'to'
#		],
#	],
#	'important' => false,
#	'track_opens' => null,
#	'track_clicks' => null,
#	'auto_text' => null,
#	'auto_html' => null,
#	'inline_css' => null,
#	'url_strip_qs' => null,
#	'preserve_recipients' => null,
#	'view_content_link' => null,
#	'bcc_address' => 'message.bcc_address@example.com',
#	'tracking_domain' => null,
#	'signing_domain' => null,
#	'return_path_domain' => null,
#	'merge' => true,
#	'merge_language' => 'mailchimp',
#	'global_merge_vars' => [
#		[
#			'name' => 'merge1',
#			'content' => 'merge1 content'
#		],
#	],
#	'merge_vars' => [
#		[
#			'rcpt' => 'recipient.email@example.com',
#			'vars' => [
#				[
#					'name' => 'merge2',
#					'content' => 'merge2 content'
#				],
#			],
#		],
#	],
#	'tags' => ['password-resets'],
#	'subaccount' => 'customer-123',
#	'google_analytics_domains' => ['example.com'],
#	'google_analytics_campaign' => 'message.from_email@example.com',
#	'metadata' => ['website' => 'www.example.com'],
#	'recipient_metadata' => [
#		[
#			'rcpt' => 'recipient.email@example.com',
#			'values' => ['user_id' => 123456],
#		]
#	],
#	'attachments' => [
#		[
#			'type' => 'text/plain',
#			'name' => 'myfile.txt',
#			'content' => 'ZXhhbXBsZSBmaWxl'
#		],
#	],
#	'images' => [
#		[
#			'type' => 'image/png',
#			'name' => 'IMAGECID',
#			'content' => 'ZXhhbXBsZSBmaWxl'
#		],
#	],
#

	/**
	* https://mandrillapp.com/api/docs/messages.php.html
	*/
	function send(array $params = [], &$error_message = '') {
		require_php_lib('mandrill');

		try {
			$message = [
				'html' => $params['html'],
				'text' => $params['text'],
				'subject' => $params['subject'],
				'from_email' => $params['email_from'],
				'from_name' => $params['name_from'],
				'headers' => ['Reply-To' => $params['reply_to'] ?: $params['from']],
			];
			if (is_array($params['email_to'])) {
				foreach ($params['email_to'] as $name => $email) {
					$message['to'][] = [
						'email' => $email,
						'name' => $name,
						'type' => 'to',
					];
				}
			} else {
				$message['to'][] = [
					'email' => $params['email_to'],
					'name' => $params['name_to'],
					'type' => 'to',
				];
			}
			if ($this->PARENT->ALLOW_ATTACHMENTS) {
				foreach ((array)$params['attaches'] as $name => $file) {
					$file_name = is_string($name) ? $name : '';
					$message['attachments'][] = [
						'type' => mime_content_type($file),
						'name' => $file_name,
						'content' => file_get_contents($file),
					];
				}
			}
			$async = false;

			$mandrill = new Mandrill($this->key);
			// $ip_pool = 'Main Pool'; // Ip pool name to use for sending
			// $send_at = 'example send_at'; // Datetime
			$result = $mandrill->messages->send($message, $async = true, $params['mandrill_ip_pool'], $params['mandrill_send_at']);
			// Example response: $result = [
			//		[
			//			[email] => recipient.email@example.com
			//			[status] => sent
			//			[reject_reason] => hard-bounce
			//			[_id] => abc123abc123abc123abc123abc123
			//		]
			//	]
		} catch(Mandrill_Error $e) {
			$error_message = 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
		}
		if (@$error_message && DEBUG_MODE && $this->PARENT->MAIL_DEBUG_ERROR) {
			trigger_error($error_message, E_USER_WARNING);
		}
		if (is_callable($params['on_after_send'])) {
			$callback = $params['on_after_send'];
			$callback($mail, $params, $result, $error_message, $this->PARENT);
		}
		$this->PARENT->_last_error_message = $error_message;
		return $result ? $result['status'] == 'sent' : false;
	}
}
