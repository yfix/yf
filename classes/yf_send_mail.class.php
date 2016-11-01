<?php

/**
* Mail sender
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_send_mail {

	/** @var string Select mailer driver to use */
	public $DRIVER					= 'phpmailer';
	/** @var string */
	public $DEFAULT_CHARSET			= 'utf-8';
	/** @var string */
	public $DEFAULT_MAILER_NAME		= 'YF PHP Mailer';
	/** @var bool */
	public $MAIL_DEBUG				= false;
	/** @var bool */
	public $MAIL_DEBUG_ERROR		= false;
	/** @var string */
	public $DEBUG_TEST_ADDRESS		= '';
	/** @var bool */
	public $DEBUG_TEST_SEND_BULK	= true;
	/** @var bool */
	public $LOG_EMAILS				= true;
	/** @var bool */
	public $DB_LOG_ENV				= false;
	/** @var bool */
	public $ALLOW_ATTACHMENTS		= true;
	/** @var bool Replaces 'From' with $smtp['smtp_from'] */
	public $REPLACE_FIELD_FROM		= true;
	/** @var callable */
	public $ON_BEFORE_SEND 			= null;
	/** @var callable */
	public $ON_AFTER_SEND 			= null;
	/** @var array SMTP specific options */
	public $SMTP_OPTIONS			= [
		'smtp_host'		=> '', // mx.test.com
		'smtp_port'		=> '25',
		'smtp_user_name'=> '', // admin@test.com
		'smtp_password'	=> '', // password here
		'smtp_auth'		=> '', // Could be: '' (default) or 'autodetect', 'login', 'plain'
		'smtp_from'		=> '', // User's account to force send mail from
		'smtp_secure'	=> '', // Could be: '' (default, empty for non-secure), 'ssl', 'tls'
	];

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Module constructor
	*/
	function _init() {
		define('LOG_MAIL_PATH', PROJECT_PATH.'logs/email/');
		$mail_debug = conf('mail_debug');
		if (isset($mail_debug)) {
			$this->MAIL_DEBUG = $mail_debug;
		}
		$test_mail = conf('test_mail');
		$test_mail && $this->DEBUG_TEST_ADDRESS = $test_mail;
		$log_emails = conf('log_emails');
		$log_emails && $this->LOG_EMAILS = $log_emails;
		// Backwards compatibility
		if (isset($this->USE_MAILER)) {
			$this->DRIVER = $this->USE_MAILER;
		}
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function send($params = []) {
		if (DEBUG_MODE || $this->LOG_EMAILS) {
			$time_start = microtime(true);
		}
		if (!isset($params['on_before_send']) && is_callable($this->ON_BEFORE_SEND)) {
			$params['on_before_send'] = $this->ON_BEFORE_SEND;
		}
		if (!isset($params['on_after_send']) && is_callable($this->ON_AFTER_SEND)) {
			$params['on_after_send'] = $this->ON_AFTER_SEND;
		}
		$_prev_num_errors = count((array)main()->_all_core_error_msgs);
		if ($this->MAIL_DEBUG_ERROR && empty($params['email_to'])) {
			trigger_error('SEND_MAIL: Missing \'To\' email address', E_USER_WARNING);
			return false;
		}
		if ($this->MAIL_DEBUG && $this->DEBUG_TEST_ADDRESS) {
			$debug_mail = $this->DEBUG_TEST_ADDRESS;
			$debug_name = '(debug: '.$name_to.' - '.$email_to.')';
			if ($this->DRIVER == 'phpmailer' && is_array($email_to)) {
				$mails = [];
				$debug_name = '';
				foreach ($email_to as $name => $email) {
					$debug_name = '(debug: '.$name.' - '.$email.')';
					$mails[$debug_name] = $debug_mail;
				}
				if ($this->DEBUG_TEST_SEND_BULK) {
					$params['email_to'] = $mails;
				} else {
					$params['email_to'] = $debug_mail;
					$params['name_to']  = implode(' - ', array_keys($mails));
				}
			} else {
				$params['email_to'] = $debug_mail;
				$params['name_to']  = $debug_name;
			}
		}
		if ($this->REPLACE_FIELD_FROM && $this->DRIVER != 'internal' && !empty($this->SMTP_OPTIONS['smtp_from_mail'])) {
			$params['email_from'] = $this->SMTP_OPTIONS['smtp_from_mail'];
			$params['name_from']  = $this->SMTP_OPTIONS['smtp_from_name'] ?: $params['name_from'];
		}
		// Go send with selected driver
		$error_message = '';
		$result = _class('send_mail_driver_'.$this->DRIVER, 'classes/send_mail/')->send($params, $error_message);

		$log = $params + [
			'email_to'           => is_array($params['email_to']) ? implode(', ', $params['email_to']) : $params['email_to'],
			'mail_debug'         => $this->MAIL_DEBUG,
			'used_mailer'        => $this->DRIVER,
			'smtp_options'       => $this->DRIVER != 'internal' ? $this->SMTP_OPTIONS : '',
			'time_start'         => $time_start,
			'send_success'       => $result ? 1 : 0,
			'error_message'      => $error_message,
		];
		if ($this->LOG_EMAILS) {
			$log['error_message'] .= implode("\n", $_prev_num_errors 
				? array_slice((array)main()->_all_core_error_msgs, $_prev_num_errors)
				: (array)main()->_all_core_error_msgs
			);
			_class('send_mail_log', 'classes/send_mail/')->save($log);
		}
		if (DEBUG_MODE) {
			$time_end = microtime(true);
			$log_data['time'] = $time_end - $time_start;
			$GLOBALS['_send_mail_debug'][] = $log;
		}
		return $result;
	}
}
