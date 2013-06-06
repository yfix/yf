<?php

/**
* Wrapper class for PEAR::Mail.
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_pear_emailer {

	/** @var string Message header */
	var $_header_template	= '';
	/** @var string Message footer */
	var $_footer_template	= '';
	/** @var string @conf_skip Email body (like HTML) */
	var $_html				= '';
	/** @var array @conf_skip */
	var $headers			= array();
	/** @var array @conf_skip Email options */
	var $options		= array(
		'to_email'		=> '', // To Email
		'to_real_name'	=> '', // To Name
		'from_email'	=> '', // From Email
		'from_real_name'=> '', // From Name
		'subject'		=> '', // Message subject
		'body'			=> '', // Message body
		'file_path'		=> '', // Path to the attached file
		'mime_type'		=> '', // Mimetype of attached file
		'reply_to'		=> '', // Field "Reply-to"
		'cc'			=> '', // Field "Cc"
		'crlf'			=> '',
		'backend'		=> 'smtp', // Default backend (could be "mail","sendmail","smtp")
		'mta_opts'		=> array( // MTA options (mixed, used with different backends)
			// Sendmail specific options
			'sendmail_path'	=> '',
			'sendmail_args'	=> '',
			// SMTP specific options
			'smtp_host'		=> '127.0.0.1',
			'smtp_port'		=> '25',
			'smtp_auth'		=> false,	// Use auth or not
			'smtp_user_name'=> '',
			'smtp_password'	=> '',
			'smtp_from'		=> '', // User's account to force send mail from
		),
	);

	/**
	* Module constructor
	* 
	* @access	private
	* @return	void
	*/
	function _init () {
		require_once "PEAR.php";
		require_once 'Mail.php';
		require_once 'Mail/mime.php';
		// Reference to the parant object (send_mail)
		$this->SEND_MAIL_OBJ = _class('send_mail', 'classes/common/');
	}

	/**
	* Do send email
	*/
	function set_options($options = array()) {
		// Merge options (max 2 dimensions array)
		foreach ((array)$options as $k => $v) {
			if (empty($k)) {
				continue;
			}
			if (is_array($v)) {
				foreach ((array)$v as $k2 => $v2) {
					$this->options[$k][$k2] = $v2;
				}
			} else {
				$this->options[$k] = $v;
			}
		}
		// Force crlf parameter
		$this->options['crlf']		= "\r\n";
		// Set email specific options
		$this->_html = $this->_header_template . $this->options['body'] . $this->_footer_template;
		$this->headers['From']		= $this->options['from_email'];
		$this->headers['Subject']	= $this->options['subject'];
		// Load SMTP config vars
		if ($this->options['backend'] == "smtp") {
			foreach ((array)$this->SEND_MAIL_OBJ->SMTP_OPTIONS as $k => $v) {
				$this->options['mta_opts'][$k] = $v;
			}
		}
	}

	/**
	* Do send email
	*/
	function send($options = array()) {
		if (!empty($options)) {
			$this->set_options($options);
		}
		// Init mail mime class
		$mime = & new Mail_mime($this->options['crlf']);
		$mime->setHTMLBody($this->_html);
		// Add attachment (currently only one allowed)
		if (!empty($this->options['file_path'])) {
			$mime->addAttachment($this->options['file_path'], $this->options['mime_type']);
		}
		// Add Cc-address
		if (!empty($this->options['cc'])) {
			$mime->addCc($this->options['cc']);
		}
		$charset = conf('charset');
		$body = $mime->get(array(
			'html_encoding' => '7bit',
			'html_charset' => $charset,
			'text_charset' => $charset,
			'head_charset' => $charset,
		));
		$hdrs = $mime->headers($this->headers);
		$mail = & yf_pear_emailer::factory();
		return $mail->send($this->options['to_email'], $hdrs, $body);
	}

	/**
	* PEAR Mail::factory wrapper
	*/
	function factory() {
		$backend = '';
		$a_params = array();
		// setup Mail::factory backend & params using site config
		switch ($this->options['backend']) {
			case '':
			case 'mail':
				$backend = 'mail';
				break;
				
			case 'sendmail':
				$backend = 'sendmail';
				$a_params['sendmail_path'] = $this->options['mta_opts']['sendmail_path'];
				$a_params['sendmail_args'] = $this->options['mta_opts']['sendmail_args'];
				break;
				
			case 'smtp':
				$backend = 'smtp';
				$a_params['host'] = (isset($this->options['mta_opts']['smtp_host']))
					? $this->options['mta_opts']['smtp_host']
					: '127.0.0.1';
				$a_params['port'] = (isset($this->options['mta_opts']['smtp_port']))
					? $this->options['mta_opts']['smtp_port']
					: 25;
				if (!empty($this->options['mta_opts']['smtp_user_name'])) {
					$a_params['username']	= $this->options['mta_opts']['smtp_user_name'];
					$a_params['password']	= $this->options['mta_opts']['smtp_password'];
					$a_params['auth']		= true;
				} else {
					$a_params['auth'] = false;
				}
				if (isset($this->options['mta_opts']['smtp_from'])) {
					$this->options['email_from'] = $this->options['mta_opts']['smtp_from'];
				}
				break;

			default: trigger_error('Unrecognised PEAR::Mail backend', E_USER_WARNING);
		}
		return Mail::factory($backend, $a_params);
	}
}
