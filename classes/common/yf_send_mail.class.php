<?php

/**
* Mail sender
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_send_mail {

	/** @var array Allowed mailers */
	var $_KNOWN_MAILERS			= array(
		"simple",
		"internal",
		"pear",
		"xpm2",
		"xpm4",
		"swift",
		"phpmailer",
	);
	/** @var string Select mailer driver to use */
	var $USE_MAILER				= "internal";
	/** @var string Force SMTP usage if availiable (phpmailer now support) */
	var $FORCE_USE_SMTP			= true;
	/** @var bool */
	var $MAIL_DEBUG				= true;
	/** @var string */
	var $DEBUG_TEST_ADDRESS		= "";
	/** @var string */
	var $DEFAULT_CHARSET		= "windows-1251";
	/** @var bool */
	var $LOG_EMAILS				= true;
	/** @var bool */
	var $DB_LOG_ENV				= false;
	/** @var bool */
	var $ALLOW_ATTACHMENTS		= true;
	/** @var array @conf_skip */
	var $PEAR_MAILER_BACKENDS	= array(
		"mail",
		"sendmail",
		"smtp",
	);
	/** @var bool Replaces "From" with $this->SMTP_OPTIONS["from"] */
	var $REPLACE_FIELD_FROM		= false;
	/** @var string External SMTP config file */
	var $_smtp_config_file		= "smtp_config.php";
	/** @var array SMTP specific options */
	var $SMTP_OPTIONS			= array(
		'smtp_host'		=> '', // mx.test.com
		'smtp_port'		=> '25',
		'smtp_user_name'=> '', // admin@test.com
		'smtp_password'	=> '', // password here
		'smtp_auth'		=> '', // Could be: "" (default) or "autodetect", "login", "plain"
		'smtp_from'		=> '', // User's account to force send mail from
		'smtp_secure'	=> '', // Could be: "" (default, empty for non-secure), "ssl", "tls"
	);

	/**
	* Module constructor
	*/
	function _init () {
		// Path to mal logs from siple sender
		define("LOG_MAIL_PATH", INCLUDE_PATH."logs/email/");
		// Prepare full path to the SMTP config file
		$this->_smtp_config_file = INCLUDE_PATH. $this->_smtp_config_file;
		// Apply some options
		$mail_debug = conf('mail_debug');
		if (isset($mail_debug)) {
			$this->MAIL_DEBUG = $mail_debug;
		}
		$test_mail = conf('test_mail');
		if (isset($test_mail)) {
			$this->DEBUG_TEST_ADDRESS = $test_mail;
		}
		$log_emails = conf('log_emails');
		if (isset($log_emails)) {
			$this->LOG_EMAILS = $log_emails;
		}
		// hide XPM4 errors
		define('DISPLAY_XPM4_ERRORS',	false);
		// log XPM4 errors
		define('LOG_XPM4_ERRORS',		true);
	}

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function send ($email_from, $name_from = "", $email_to = "", $name_to = "", $subject = "", $text = "", $html = "", $attaches = array(), $charset = "", $pear_mailer_backend = "smtp", $force_mta_opts = array(), $priority = 3) {
		if (DEBUG_MODE) {
			$time_start = microtime(true);
		}
		// We have params passed as array
		if (is_array($email_from)) {
			$params = $email_from;
			$email_from		= $params["from_mail"];
			$name_from		= $params["from_name"];
			$email_to		= $params["to_mail"];
			$name_to		= $params["to_name"];
			$subject		= $params["subj"];
			$text			= $params["text"];
			$html			= $params["html"];
			$attaches		= is_array($params["attach"]) ? $params["attach"] : array();
			$charset		= $params["charset"];
			$force_mta_opts = $params["force_mta_opts"] ? $params["force_mta_opts"] : array();
			$priority		= $params["priority"] ? $params["priority"] : 3;
			$pear_mailer_backend = $params["pear_mailer_backend"] ? $params["pear_mailer_backend"] : "smtp";
		}
		$_prev_num_errors = count((array)main()->_all_core_error_msgs);
		// Check required params
		if (empty($email_to)) {
			trigger_error("SEND_MAIL: Missing \"To\" email address", E_USER_WARNING);
			return false;
		}
		if ($this->LOG_EMAILS) {
			$_time_start = microtime(true);
		}
		// Debug mail (All emails are sending to the specified email address)
		if ($this->MAIL_DEBUG && $this->DEBUG_TEST_ADDRESS) {
			$email_to = $this->DEBUG_TEST_ADDRESS;
		}
		// Load specific SMTP options (only for "pear", "xpm2", "xpm4")
		if (in_array($this->USE_MAILER, array("pear","xpm2","xpm4","swift","phpmailer"))) {
			// Try to get specific SMTP settings
			$this->SMTP_OPTIONS = $this->_process_smtp_config($email_to);
		}

		$result = false;
		$error_message = "";
		// Replaces "From:" field
		if ($this->REPLACE_FIELD_FROM && $this->USE_MAILER != "internal" && !empty($this->SMTP_OPTIONS["from"])) {
			$email_from = $this->SMTP_OPTIONS["from"];
			$name_from	= $email_from;
		}
		// Try to use PEAR mailer
		if ($this->USE_MAILER == "pear") {

			$options		= array(
				'to_email'		=> $email_to,
				'to_real_name'	=> $name_to,
				'from_email'	=> $email_from,
				'from_real_name'=> $name_from,
				'subject'		=> $subject,
				'body'			=> $html,
				'backend'		=> in_array($pear_mailer_backend, $this->PEAR_MAILER_BACKENDS) ? $pear_mailer_backend : "mail",
			);
			// Force mta options
			if (!empty($force_mta_opts) && is_array($force_mta_opts)) {
				$options['mta_opts'] = $force_mta_opts;
			}
			$result = _class("pear_emailer", COMMON_LIB)->send($options);

		// Try to use XPM2 mailer
		} elseif ($this->USE_MAILER == "xpm2") {

			// path to smtp.php from XPM2 package
			require_once PF_PATH.'libs/xpm2/smtp.php';
			// Process options
			$mailer = new SMTP;
			if (!empty($this->SMTP_OPTIONS["smtp_host"])) {
				$mailer->Delivery('relay');
				$mailer->Relay(
					$this->SMTP_OPTIONS["smtp_host"]
					, !empty($this->SMTP_OPTIONS["smtp_user_name"]) ? $this->SMTP_OPTIONS["smtp_user_name"] : false
					, !empty($this->SMTP_OPTIONS["smtp_password"]) ? $this->SMTP_OPTIONS["smtp_password"] : false
					, !empty($this->SMTP_OPTIONS["smtp_port"]) ? intval($this->SMTP_OPTIONS["smtp_port"]) : 25
					, !empty($this->SMTP_OPTIONS["smtp_auth"]) ? $this->SMTP_OPTIONS["smtp_auth"] : 'autodetect'
					, !empty($this->SMTP_OPTIONS["smtp_secure"]) ? $this->SMTP_OPTIONS["smtp_secure"] : false
				);
			}
			// Set different "Reply-To" field if needed
			if (defined("SITE_ADMIN_EMAIL")) {
				$mailer->From(SITE_ADMIN_EMAIL, "noreply");
				$mailer->addheader("Reply-To", $name_from."<".$email_from.">", "utf-8", '');
			} else {
				$mailer->From($email_from, $name_from);
			}
			$mailer->AddTo($email_to, $name_to);
			$mailer->Text($text);
			$mailer->Html($html);
			if ($this->ALLOW_ATTACHMENTS) {
				foreach ((array)$attaches as $cur_file) {
					$mailer->AttachFile($cur_file);
				}
			}
			// Go!
			$result = $mailer->Send($subject, !empty($charset) ? $charset : 'utf-8');

		// Try to use XPM4 mailer
		} elseif ($this->USE_MAILER == "xpm4") {

			require_once PF_PATH.'libs/xpm4/MAIL.php';
	    	// Prepare
			$mailer = new MAIL;
			// Set different "Reply-To" field if needed
			if (defined("SITE_ADMIN_EMAIL")) {
				$mailer->From(SITE_ADMIN_EMAIL, "noreply");
				$mailer->addheader("Reply-To", "noreply"."<".SITE_ADMIN_EMAIL.">", "utf-8", '');
			} else {
				$mailer->From($email_from, $name_from);
			}
			$mailer->AddTo($email_to, $name_to);
			$mailer->Text($text);
			$mailer->Html($html);
			$mailer->Subject($subject);
			if ($this->ALLOW_ATTACHMENTS) {
				foreach ((array)$attaches as $cur_file) {
					$mailer->AttachFile($cur_file);
				}
			}
			// make sure you have OpenSSL module (extension) enable on your php configuration
			$connection = $mailer->Connect(
				$this->SMTP_OPTIONS["smtp_host"]
				, !empty($this->SMTP_OPTIONS["smtp_port"]) ? intval($this->SMTP_OPTIONS["smtp_port"]) : 25
				, !empty($this->SMTP_OPTIONS["smtp_user_name"]) ? $this->SMTP_OPTIONS["smtp_user_name"] : false
				, !empty($this->SMTP_OPTIONS["smtp_password"]) ? $this->SMTP_OPTIONS["smtp_password"] : false
				, !empty($this->SMTP_OPTIONS["smtp_secure"]) ? $this->SMTP_OPTIONS["smtp_secure"] : false
			);
			if (is_resource($connection)) {
				$result = $mailer->Send($c);
				$mailer->Disconnect();
			} else {
				$error_message .= "Can't connect to SMTP server, Reason: <br />";
				$error_message .= print_r($mailer->Result, 1);
			}

		// Try to use Swift mailer
		} elseif ($this->USE_MAILER == "swift") {

			require_once PF_PATH. "/swift/lib/Swift.php";
			require_once PF_PATH. "/swift/lib/Swift/Connection/SMTP.php";

			$conn = new Swift_Connection_SMTP($this->SMTP_OPTIONS["smtp_host"], $this->SMTP_OPTIONS["smtp_port"], $this->SMTP_OPTIONS["smtp_secure"] == "tls" ? SWIFT_SMTP_ENC_TLS : false);
			$conn->setUsername($this->SMTP_OPTIONS["smtp_user_name"]);
			$conn->setPassword($this->SMTP_OPTIONS["smtp_password"]);

			$swift	=& new Swift($conn);
			$result = $swift->send(
				new Swift_Message($subject, $text)
				, new Swift_Address($email_from, $name_from)
				, new Swift_Address($email_to, $name_to)
			);

		// Try to use PHPMailer mailer
		} elseif ($this->USE_MAILER == "phpmailer") {

			require_once(PF_PATH."libs/phpmailer/class.phpmailer.php");
			
			$mail = new PHPMailer(true); // defaults to using php "mail()"
			try {
				$mail->CharSet	= "utf-8";
				$mail->From		= $email_from;
				if ($name_from) {
					$mail->FromName = $name_from;
				}
				$mail->SetFrom($email_from, $name_from ? $name_from : $email_from);
				if(DEBUG_MODE){
					$mail->SMTPDebug  = 1;
				}
//				$mail->AddReplyTo($email_from, $name_from);
				$mail->AddAddress($email_to, $name_to);
				$mail->Subject	= $subject;
			    
				$mail->IsHTML(true);
				$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";
				$mail->MsgHTML($html);
			    
				if ($this->ALLOW_ATTACHMENTS) {
					foreach ((array)$attaches as $cur_file) {
						$mail->AddAttachment($cur_file);
					}
				}

				if ($this->FORCE_USE_SMTP && $this->SMTP_OPTIONS["smtp_host"]) {
					$mail->IsSMTP();
					$mail->Host		= $this->SMTP_OPTIONS["smtp_host"];
					$mail->Port		= $this->SMTP_OPTIONS["smtp_port"];
					$mail->SMTPAuth = true;
					$mail->Username = $this->SMTP_OPTIONS["smtp_user_name"];
					$mail->Password = $this->SMTP_OPTIONS["smtp_password"];
					if ($this->SMTP_OPTIONS["smtp_secure"]) {
						$mail->SMTPSecure = $this->SMTP_OPTIONS["smtp_secure"];
					}
				}

				$result = $mail->Send();

			} catch (phpmailerException $e) {

				$error_message .= $e->errorMessage(); //Pretty error messages from PHPMailer

			} catch (Exception $e) {
				$error_message .= $e->getMessage(); //Boring error messages from anything else!
			}

			if (!$result) {
				$error_message .= $mail->ErrorInfo;
			}
			
			if(DEBUG_MODE){
				echo $error_message;
			}
			
		// Internal Framework mailer
		} elseif ($this->USE_MAILER == "internal") {

			// Send email using old lightweight lib
			$result = $this->_simple_send($email_from, $name_from, $email_to, $name_to, $subject, $text, $html, $attaches, $charset, $priority);

		// Simple using mail()
		} elseif ($this->USE_MAILER == "simple") {

			$result = mail($email_to, $subject, $text/*, ($email_from ? 'From: '.$email_from : null)*/);

		} else {

			trigger_error("SEND_MAIL: Wrong USE_MAILER value:'".$this->USE_MAILER."'" , E_USER_WARNING);

		}
		$log_data = array(
			"email_from"	=> $email_from,
			"name_from"		=> $name_from,
			"email_to"		=> $email_to,
			"name_to"		=> $name_to,
			"subject"		=> $subject,
			"text"			=> $text,
			"attaches"		=> $attaches,
			"charset"		=> $charset,
			"mail_debug"	=> $this->MAIL_DEBUG,
			"used_mailer"	=> $this->USE_MAILER,
			"smtp_options"	=> in_array($this->USE_MAILER, array("pear","xpm2","xpm4")) ? $this->SMTP_OPTIONS : "",
			"time_start"	=> $_time_start,
			"send_success"	=> $result ? 1 : 0,
			"error_message"	=> $error_message,
		);
		// Do log email if needed
		if ($this->LOG_EMAILS) {
			$error_message .= implode("\n", $_prev_num_errors ? array_slice((array)main()->_all_core_error_msgs, $_prev_num_errors) : (array)main()->_all_core_error_msgs);
			$this->_save_log($log_data);
		}
		if (DEBUG_MODE) {
			$time_end = microtime(true);

			$log_data["time"] = $time_end - $time_start;
			$GLOBALS["_send_mail_debug"][] = $log_data;
		}
		return $result;
	} 

	/**
	* Send emails with attachments with DEBUG ability
	*/
	function _simple_send ($email_from, $name_from, $email_to, $name_to, $subject, $text = "", $html = "", $attaches = array(), $charset = "", $priority = 3) {
		if (!strlen($charset)) {
			$charset = conf('charset');
		}
		if (!strlen($charset)) {
			$charset = $this->DEFAULT_CHARSET ? $this->DEFAULT_CHARSET : "utf-8";
		}
		$mailer_name = "YF PHP Mailer";

		$text = $text ? $text : "Sorry, but you need an html mailer to read this mail.";

		$OB = "----=_OuterBoundary_000";
    	$IB = "----=_InnerBoundery_001";
		$headers  = "MIME-Version: 1.0\r\n"; 
		// Strange behaviour on windows,
		if (OS_WINDOWS) {
			$headers .= $email_from	? "From:".$email_from."\r\n"		: "";
			$headers .= $email_to	? "To:".$email_to."\r\n"			: "";
			$headers .= $email_from ? "Reply-To:".$email_from."\r\n"	: "";
		} else {
			$headers .= $email_from	? "From:".$name_from."<".$email_from.">\r\n"		: "";
			$headers .= $email_to	? "To:".$name_to."<".$email_to.">\r\n"				: "";
			$headers .= $email_from ? "Reply-To:".$name_from."<".$email_from.">\r\n"	: "";
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
			if (preg_match("/".$pattern."/ims", $email_address)) {
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
		$other_options = "";
		if (!empty($params["attaches"])) {
			$other_options .= "attaches:".implode(",", $params["attaches"])."\r\n";
		}
		if (!empty($params["charset"])) {
			$other_options .= "charset:".$params["charset"]."\r\n";
		}
		$smtp = $params["smtp_options"];
		if (!empty($smtp)) {
			$other_options .= 
				"smtp_host:".$smtp["smtp_host"]
				.", smtp_user: ".$smtp["smtp_user_name"]
				.", smtp_port: ".$smtp["smtp_port"]
				.", smtp_secure: ".$smtp["smtp_secure"]
				."\r\n";
		}
		// Prepare SQL
		$sql = db()->INSERT("log_emails", array(
			"email_from"	=> _es($params["email_from"]),
			"name_from"		=> _es($params["name_from"]),
			"email_to"		=> _es($params["email_to"]),
			"name_to"		=> _es($params["name_to"]),
			"subject"		=> _es($params["subject"]),
			"text"			=> _es($params["text"]),
			"source_file"	=> _es($cur_trace["file"]),
			"source_line"	=> intval($cur_trace["line"]),
			"date"			=> time(),
			"site_id"		=> (int)conf('SITE_ID'),
			"user_id"		=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_id" : "user_id"]),
			"user_group"	=> intval($_SESSION[MAIN_TYPE_ADMIN ? "admin_group" : "user_group"]),
			"is_admin"		=> MAIN_TYPE_ADMIN ? 1 : 0,
			"ip"			=> _es(common()->get_ip()),
			"query_string"	=> _es(WEB_PATH."?".$_SERVER["QUERY_STRING"]),
			"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
			"referer"		=> _es($_SERVER["HTTP_REFERER"]),
			"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
			"env_data"		=> $this->DB_LOG_ENV ? _es(serialize(array("_GET"=>$_GET,"_POST"=>$_POST))) : "",
			"object"		=> _es($_GET["object"]),
			"action"		=> _es($_GET["action"]),
			"success"		=> intval((bool)$params["send_success"]),
			"error_text"	=> _es($params["error_message"]),
			"send_time"		=> floatval(common()->_format_time_value(microtime(true) - (float)$params["time_start"])),
			"mail_debug"	=> intval((bool)$params["mail_debug"]),
			"used_mailer"	=> _es($params["used_mailer"]),
			"other_options"	=> _es($other_options),
		), true);
//		db()->_add_shutdown_query($sql);
		db()->query($sql);
	}
}
