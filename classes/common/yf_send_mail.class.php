<?php

/**
* Mail sender
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_send_mail {

	/** @var array Allowed mailers */
	public $_KNOWN_MAILERS			= array(
		'simple',
		'internal',
		'phpmailer',
	);
	/** @var string Select mailer driver to use */
	public $USE_MAILER				= 'internal';
	/** @var string Force SMTP usage if availiable (phpmailer now support) */
	public $FORCE_USE_SMTP			= true;
	/** @var bool */
	public $MAIL_DEBUG				= false;
	/** @var string */
	public $DEBUG_TEST_ADDRESS		= '';
	/** @var bool */
	public $DEBUG_TEST_SEND_BULK	= true;
	/** @var string */
	public $DEFAULT_CHARSET		= 'windows-1251';
	/** @var bool */
	public $LOG_EMAILS				= true;
	/** @var bool */
	public $DB_LOG_ENV				= false;
	/** @var bool */
	public $ALLOW_ATTACHMENTS		= true;
	/** @var bool Replaces 'From' with $this->SMTP_OPTIONS['from'] */
	public $REPLACE_FIELD_FROM		= true;
	/** @var string External SMTP config file */
	public $_smtp_config_file		= 'smtp_config.php';
	/** @var array SMTP specific options */
	public $SMTP_OPTIONS			= array(
		'smtp_host'		=> '', // mx.test.com
		'smtp_port'		=> '25',
		'smtp_user_name'=> '', // admin@test.com
		'smtp_password'	=> '', // password here
		'smtp_auth'		=> '', // Could be: '' (default) or 'autodetect', 'login', 'plain'
		'smtp_from'		=> '', // User's account to force send mail from
		'smtp_secure'	=> '', // Could be: '' (default, empty for non-secure), 'ssl', 'tls'
	);

	/**
	* Module constructor
	*/
	function _init () {
		define('LOG_MAIL_PATH', INCLUDE_PATH.'logs/email/');
		$this->_smtp_config_file = INCLUDE_PATH. $this->_smtp_config_file;
		$mail_debug = conf('mail_debug');
		if (isset($mail_debug)) {
			$this->MAIL_DEBUG = $mail_debug;
		}
		$test_mail = conf('test_mail');
		if (!empty($test_mail)) {
			$this->DEBUG_TEST_ADDRESS = $test_mail;
		}
		$log_emails = conf('log_emails');
		if (!empty($log_emails)) {
			$this->LOG_EMAILS = $log_emails;
		}
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function send ($email_from, $name_from = '', $email_to = '', $name_to = '', $subject = '', $text = '', $html = '', $attaches = array(), $charset = '', $_deprecated_param1 = '', $force_mta_opts = array(), $priority = 3, $smtp = array()) {
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// We have params passed as array
		if (is_array($email_from)) {
			$params = $email_from;
			$email_from		= $params['from_mail'];
			$name_from		= $params['from_name'];
			$email_to		= $params['to_mail'];
			$name_to		= $params['to_name'];
			$subject		= $params['subj'];
			$text			= $params['text'];
			$html			= $params['html'];
			$attaches		= is_array($params['attach']) ? $params['attach'] : $attaches;
			$charset		= $params['charset'];
			$force_mta_opts = $params['force_mta_opts'] ? $params['force_mta_opts'] : $force_mta_opts;
			$priority		= $params['priority'] ? $params['priority'] : 3;
			$smtp			= $params['smtp'] ?: $smtp;
		}
		$_prev_num_errors = count((array)main()->_all_core_error_msgs);
		// Check required params
		if (empty($email_to)) {
			trigger_error('SEND_MAIL: Missing \'To\' email address', E_USER_WARNING);
			return false;
		}
		if ($this->LOG_EMAILS) {
			$_time_start = microtime(true);
		}
		// Debug mail (All emails are sending to the specified email address)
		if ($this->MAIL_DEBUG && $this->DEBUG_TEST_ADDRESS) {
			$debug_mail = $this->DEBUG_TEST_ADDRESS;
			$debug_name = "(debug: $name_to - $email_to)";
			if ($this->USE_MAILER == 'phpmailer' && is_array($email_to)) {
				$mails = array();
				$debug_name = '';
				foreach( $email_to as $name => $email ) {
					$debug_name = "(debug: $name - $email)";
					$mails[ $debug_name ] = $debug_mail;
				}
				if( $this->DEBUG_TEST_SEND_BULK ) {
					$email_to = $mails;
				} else {
					$email_to = $debug_mail;
					$name_to  = implode( ' - ', array_keys( $mails ) );
				}
			} else {
				$email_to = $debug_mail;
				$name_to  = $debug_name;
			}
		}

		// Load specific SMTP options (only for 'phpmailer')
		if ( !$this->MAIL_DEBUG && empty( $smtp ) && in_array($this->USE_MAILER, array('phpmailer'))) {
			// Try to get specific SMTP settings
			$this->SMTP_OPTIONS = $this->_process_smtp_config($email_to);
		}

		if( !$this->MAIL_DEBUG && !empty( $smtp ) ) {
			$this->SMTP_OPTIONS = $smtp;
		}

		$result = false;
		$error_message = '';
		// Replaces 'From:' field
		if ($this->REPLACE_FIELD_FROM && $this->USE_MAILER != 'internal' && !empty($this->SMTP_OPTIONS['smtp_from_mail'])) {
			$email_from = $this->SMTP_OPTIONS['smtp_from_mail'];
			$name_from  = $this->SMTP_OPTIONS['smtp_from_name'] ?: $email_from;
		}

		// Try to use PHPMailer mailer
		if ($this->USE_MAILER == 'phpmailer') {

			require_once(YF_PATH.'libs/phpmailer/class.phpmailer.php');

			$mail = new PHPMailer(true); // defaults to using php 'mail()'
			try {
				$mail->CharSet  = $charset ?: $this->DEFAULT_CHARSET;
				$mail->From     = $email_from;
				$mail->FromName = $name_from;
				if( DEBUG_MODE && $this->MAIL_DEBUG ) {
					$mail->SMTPDebug = 1;
					$mail->Debugoutput = 'error_log';
				}
				if (is_array($email_to)) {
					list( $name, $email ) = each( $email_to ); array_shift( $email_to );
					$mail->AddAddress($email, $name);
				} else {
					$mail->AddAddress($email_to, $name_to);
				}
				$mail->Subject = $subject;
				if (empty($html)) {
					$mail->Body = $text;
				} else {
					$mail->IsHTML(true);
					$mail->Body    = $html;
					$mail->AltBody = $text;
				}
				if ($this->ALLOW_ATTACHMENTS) {
					foreach ((array)$attaches as $name => $file) {
						$file_name = is_string( $name ) ? $name: '';
						$mail->AddAttachment($file, $file_name );
					}
				}
				if ($this->FORCE_USE_SMTP && $this->SMTP_OPTIONS['smtp_host']) {
					$smtp_options = &$this->SMTP_OPTIONS;
					$mail->IsSMTP();
					$mail->Host       = $smtp_options[ 'smtp_host'      ];
					$mail->Port       = $smtp_options[ 'smtp_port'      ];
					$mail->SMTPAuth   = $smtp_options[ 'smtp_auth'      ];
					$mail->Username   = $smtp_options[ 'smtp_user_name' ];
					$mail->Password   = $smtp_options[ 'smtp_password'  ];
					$mail->SMTPSecure = $smtp_options[ 'smtp_secure'    ] ?: false;
				}
				$result = $mail->Send();
				if (is_array($email_to) && !empty( $email_to )) {
					foreach( $email_to as $name => $email ) {
						$mail->clearAddresses();
						$mail->AddAddress($email, $name);
						$r = $mail->Send();
						$result = $result && $r;
					}
				}
			} catch (phpmailerException $e) {
				$error_message .= $e->errorMessage(); //Pretty error messages from PHPMailer
			} catch (Exception $e) {
				$error_message .= $e->getMessage(); //Boring error messages from anything else!
			}
			if (!$result) {
				$error_message .= $mail->ErrorInfo;
			}
			if( DEBUG_MODE && $this->MAIL_DEBUG ) {
				echo $error_message;
			}
		// Internal Framework mailer
		} elseif ($this->USE_MAILER == 'internal') {
			// Send email using old lightweight lib
			$result = $this->_simple_send($email_from, $name_from, $email_to, $name_to, $subject, $text, $html, $attaches, $charset, $priority);
		// Simple using mail()
		} elseif ($this->USE_MAILER == 'simple') {
			$result = mail($email_to, $subject, $text/*, ($email_from ? 'From: '.$email_from : null)*/);
		} else {
			trigger_error('SEND_MAIL: Wrong USE_MAILER value: '.$this->USE_MAILER , E_USER_WARNING);
		}

		$log_data = array(
			'email_from'         => $email_from,
			'name_from'          => $name_from,
			'email_to'           => implode( ', ', (array)$email_to ),
			'name_to'            => $name_to,
			'subject'            => $subject,
			'text'               => $text,
			'attaches'           => $attaches,
			'charset'            => $charset,
			'mail_debug'         => $this->MAIL_DEBUG,
			'used_mailer'        => $this->USE_MAILER,
			'smtp_options'       => $this->USE_MAILER != 'internal' ? $this->SMTP_OPTIONS : '',
			'time_start'         => $_time_start,
			'send_success'       => $result ? 1 : 0,
			'error_message'      => $error_message,
		);
		// Do log email if needed
		if ($this->LOG_EMAILS) {
			$error_message .= implode("\n", $_prev_num_errors ? array_slice((array)main()->_all_core_error_msgs, $_prev_num_errors) : (array)main()->_all_core_error_msgs);
			$this->_save_log($log_data);
		}
		if (DEBUG_MODE) {
			$time_end = microtime(true);

			$log_data['time'] = $time_end - $time_start;
			$GLOBALS['_send_mail_debug'][] = $log_data;
		}
		return $result;
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function _simple_send ($email_from, $name_from, $email_to, $name_to, $subject, $text = '', $html = '', $attaches = array(), $charset = '', $priority = 3) {
		if (!strlen($charset)) {
			$charset = conf('charset');
		}
		if (!strlen($charset)) {
			$charset = $this->DEFAULT_CHARSET ? $this->DEFAULT_CHARSET : 'utf-8';
		}
		$mailer_name = 'YF PHP Mailer';

		$text = $text ? $text : 'Sorry, but you need an html mailer to read this mail.';

		$OB = '----=_OuterBoundary_000';
		$IB = '----=_InnerBoundery_001';
		$headers  = 'MIME-Version: 1.0'."\r\n";
		// Strange behaviour on windows,
		if (OS_WINDOWS) {
			$headers .= $email_from	? 'From:'.$email_from."\r\n"		: '';
			$headers .= $email_to	? 'To:'.$email_to."\r\n"			: '';
			$headers .= $email_from ? 'Reply-To:'.$email_from."\r\n"	: '';
		} else {
			$headers .= $email_from	? 'From:'.$name_from.'<'.$email_from.">\r\n"		: '';
			$headers .= $email_to	? 'To:'.$name_to.'<'.$email_to.">\r\n"				: '';
			$headers .= $email_from ? 'Reply-To:'.$name_from.'<'.$email_from.">\r\n"	: '';
		}
		$headers .= "X-Priority:".intval($priority)."\r\n";
		$headers .= "X-Mailer:".$mailer_name."\r\n";
		$headers .= "Content-Type:multipart/mixed;\r\n\tboundary=\"".$OB."\"\r\n";
		// Messages start with text/html alternatives in OB
		$msg  = "This is a multi-part message in MIME format.\r\n";
		$msg .= "\r\n--".$OB."\r\n";
		if (strlen($text) || strlen($html)) {
			$msg .= "Content-Type: multipart/alternative;\r\n\tboundary=\"".$IB."\"\r\n\r\n";
		}
		// plaintext section
		if (strlen($text)) {
			$msg .= "\r\n--".$IB."\r\n";
			$msg .= "Content-Type: text/plain;\r\n\tcharset=\"".$charset."\"\r\n";
			$msg .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
			// plaintext goes here
			$msg .= $text."\r\n\r\n";
		}
		// html section
		if (strlen($html)) {
			$msg .= "\r\n--".$IB."\r\n";
			$msg .= "Content-Type: text/html;\r\n\tcharset=\"".$charset."\"\r\n";
			$msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
			// html goes here
			$msg .= chunk_split(base64_encode($html))."\r\n\r\n";
		}
		// end of IB
		if (strlen($text) || strlen($html)) {
			$msg .= "\r\n--".$IB."--\r\n";
		}
		// attachments
		if ($this->ALLOW_ATTACHMENTS) {
			foreach ((array)$attaches as $att_file) {
				$file_name = basename($att_file);
				$msg .= "\r\n--".$OB."\r\n";
				$msg .= "Content-Type: application/octetstream;\r\n\tname=\"".$file_name."\"\r\n";
				$msg .= "Content-Transfer-Encoding: base64\r\n";
				$msg .= "Content-Disposition: attachment;\r\n\tfilename=\"".$file_name."\"\r\n\r\n";
				// file goes here
				$msg .= chunk_split(base64_encode(@file_get_contents($att_file)));
				$msg .= "\r\n\r\n";
			}
		}
		// message ends
		$msg .= "\r\n--".$OB."--\r\n";
		// Send composed email
		return mail($email_to, $subject, $msg, $headers);
	}

	/**
	* SMTP config init
	*/
	function _load_smtp_config() {
		if (conf('smtp_patterns')) {
			return true;
		}
		// Try to get settings from external file
#		if (file_exists($this->_smtp_config_file)) {
#			include_once ($this->_smtp_config_file);
#		}
	}

	/**
	* Try to set specific SMTP server options for the matched email address
	*/
	function _process_smtp_config($email_address) {
		$cur_smtp_options = $this->SMTP_OPTIONS;
		// Try to load SMTP config file
		if (!conf('smtp_accounts')) {
			$this->_load_smtp_config();
		}
		// Check for required data
		if (!conf('smtp_accounts')) {
			return false;
		}
		// If no specific patterns found - then use first SMTP account for all emails
		if (!conf('smtp_patterns')) {
			$cur_smtp_options = array_pop(conf('smtp_accounts'));
		}
		// Process patterns
		foreach ((array)conf('smtp_patterns') as $pattern => $smtp_account_to_use) {
			if (preg_match('/'.$pattern.'/ims', $email_address)) {
				$cur_smtp_options = conf('smtp_accounts::'.$smtp_account_to_use);
				$SPECIFIC_ACCOUNT_FOUND = true;
				break;
			}
		}
		// If no specific account patterns matched - then use default (first) account
		if (!$SPECIFIC_ACCOUNT_FOUND) {
			$cur_smtp_options = array_pop(conf('smtp_accounts'));
		}
		return $cur_smtp_options;
	}

	/**
	* Save email log info
	*/
	function _save_log ($params = array()) {
		if (!$this->LOG_EMAILS) {
			return false;
		}
		// Try to get user error message source
		$backtrace = debug_backtrace();
		$cur_trace	= $backtrace[2];
		// Prepare other options
		$other_options = '';
		if (!empty($params['attaches'])) {
			$other_options .= 'attaches:'.implode(',', $params['attaches'])."\r\n";
		}
		if (!empty($params['charset'])) {
			$other_options .= 'charset:'.$params['charset']."\r\n";
		}
		$smtp = $params['smtp_options'];
		if (!empty($smtp)) {
			$other_options .=
				'smtp_host:'.$smtp['smtp_host']
				.', smtp_user: '.$smtp['smtp_user_name']
				.', smtp_port: '.$smtp['smtp_port']
				.', smtp_secure: '.$smtp['smtp_secure']
				."\r\n";
		}
		$sql = db()->INSERT('log_emails', array(
			'email_from'	=> _es($params['email_from']),
			'name_from'		=> _es($params['name_from']),
			'email_to'		=> _es($params['email_to']),
			'name_to'		=> _es($params['name_to']),
			'subject'		=> _es($params['subject']),
			'text'			=> _es($params['text']),
			'source_file'	=> _es($cur_trace['file']),
			'source_line'	=> intval($cur_trace['line']),
			'date'			=> time(),
			'site_id'		=> (int)conf('SITE_ID'),
			'user_id'		=> intval($_SESSION[MAIN_TYPE_ADMIN ? 'admin_id' : 'user_id']),
			'user_group'	=> intval($_SESSION[MAIN_TYPE_ADMIN ? 'admin_group' : 'user_group']),
			'is_admin'		=> MAIN_TYPE_ADMIN ? 1 : 0,
			'ip'			=> _es(common()->get_ip()),
			'query_string'	=> _es(WEB_PATH.'?'.$_SERVER['QUERY_STRING']),
			'user_agent'	=> _es($_SERVER['HTTP_USER_AGENT']),
			'referer'		=> _es($_SERVER['HTTP_REFERER']),
			'request_uri'	=> _es($_SERVER['REQUEST_URI']),
			'env_data'		=> $this->DB_LOG_ENV ? _es(serialize(array('_GET'=>$_GET,'_POST'=>$_POST))) : '',
			'object'		=> _es($_GET['object']),
			'action'		=> _es($_GET['action']),
			'success'		=> intval((bool)$params['send_success']),
			'error_text'	=> _es($params['error_message']),
			'send_time'		=> floatval(common()->_format_time_value(microtime(true) - (float)$params['time_start'])),
			'mail_debug'	=> intval((bool)$params['mail_debug']),
			'used_mailer'	=> _es($params['used_mailer']),
			'other_options'	=> _es($other_options),
		), true);
		db()->query($sql);
	}
}
