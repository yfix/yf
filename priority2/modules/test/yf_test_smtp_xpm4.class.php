<?php

/**
* Test sub-class
*/
class yf_test_smtp_xpm4 {

	/**
	* Profy module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* test XPM4 mailer
	*/
	function run_test () {
		$time_start = microtime(true);
		// Seth path to XPM4
//		set_include_path (YF_PATH."libs/xpm4". PATH_SEPARATOR. get_include_path());

		define('DISPLAY_XPM4_ERRORS', false);	// display XPM4 errors
		define('LOG_XPM4_ERRORS', true);		// log XPM4 errors

		require_once YF_PATH.'libs/xpm4/MAIL.php';

		$mailer = new MAIL;
		$mailer->From($this->TEST_OBJ->TEST_MAIL["email_from"], $this->TEST_OBJ->TEST_MAIL["name_from"]);
		$mailer->AddTo($this->TEST_OBJ->TEST_MAIL["email_to"], $this->TEST_OBJ->TEST_MAIL["name_to"]);
		$mailer->Text($this->TEST_OBJ->TEST_MAIL["text"]);
		$mailer->Html($this->TEST_OBJ->TEST_MAIL["html"]);
		$mailer->Subject($this->TEST_OBJ->TEST_MAIL["subject"]." by xpm4");
		// make sure you have OpenSSL module (extension) enable on your php configuration
		$c = $mailer->Connect(
			$this->TEST_OBJ->SMTP_OPTIONS["smtp_host"]
			, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_port"]) ? intval($this->TEST_OBJ->SMTP_OPTIONS["smtp_port"]) : 25
			, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"] : false
			, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_password"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_password"] : false
			, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"] : false
		);
		if (is_resource($c)) {
			$result = $mailer->Send($c);
			$mailer->Disconnect();
		} else {
			$error .= "Can't connect to SMTP server, Reason: <br />";
			$error .= print_r($mailer->Result, 1);
//			$body .= "<br />History:<br /> <small>".print_r($mailer->History, 1)."</small><br />";
		}
		$body .= $result ? "<b style='color:green;'>Send successful</b>" : "<b style='color:red;'>Send failed</b>";
		$body .= !$result ? "<br /><b>Reason:</b><br /> ".($error ? $error."<br />" : "")." ".implode("<br />\n", (array)main()->_all_core_error_msgs)."<br />" : "";
		$body .= "<br />Spent time: ".common()->_format_time_value(microtime(true) - $time_start)." sec.<br />";
		return $body;
	}
}
