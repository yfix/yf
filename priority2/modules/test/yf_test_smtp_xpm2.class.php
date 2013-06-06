<?php

/**
* Test sub-class
*/
class yf_test_smtp_xpm2 {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* test XPM2 mailer
	*/
	function run_test () {
		$time_start = microtime(true);
		// path to smtp.php from XPM2 package
		require_once YF_PATH.'libs/xpm2/smtp.php';
		// Process options
		$mailer = new SMTP;
		if (!empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_host"])) {
			$mailer->Delivery('relay');
			$mailer->Relay(
				$this->TEST_OBJ->SMTP_OPTIONS["smtp_host"]
				, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"] : false
				, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_password"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_password"] : false
				, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_port"]) ? intval($this->TEST_OBJ->SMTP_OPTIONS["smtp_port"]) : 25
				, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_auth"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_auth"] : 'autodetect'
				, !empty($this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"]) ? $this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"] : false
			);
		}
		$mailer->From($this->TEST_OBJ->TEST_MAIL["email_from"], $this->TEST_OBJ->TEST_MAIL["name_from"]);
		// Set different "Reply-To" field if needed
//		$mailer->addheader("Reply-To", $this->TEST_OBJ->TEST_MAIL["name_from"]."<".$this->TEST_OBJ->TEST_MAIL["email_from"].">", "utf-8", '');
		$mailer->AddTo($this->TEST_OBJ->TEST_MAIL["email_to"], $this->TEST_OBJ->TEST_MAIL["name_to"]);
		$mailer->Text($this->TEST_OBJ->TEST_MAIL["text"]);
		$mailer->Html($this->TEST_OBJ->TEST_MAIL["html"]);
		// Go!
		$result = $mailer->Send($this->TEST_OBJ->TEST_MAIL["subject"]." by xpm2", 'utf-8');
		$body .= $result ? "<b style='color:green;'>Send successful</b>" : "<b style='color:red;'>Send failed</b>";
		$body .= !$result ? "<br /><b>Reason:</b><br /> ".implode("<br />\n", (array)main()->_all_core_error_msgs)."<br />" : "";
		$body .= "<br />Spent time: ".common()->_format_time_value(microtime(true) - $time_start)." sec.<br />";
		return $body;
	}
}
