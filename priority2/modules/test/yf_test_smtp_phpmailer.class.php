<?php

/**
* Test sub-class
*/
class yf_test_smtp_phpmailer {

	/**
	* Profy module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* test PHPMailer
	*/
	function run_test () {
		$time_start = microtime(true);

		require_once(PF_PATH."libs/phpmailer/class.phpmailer.php");

		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->Host		= $this->TEST_OBJ->SMTP_OPTIONS["smtp_host"];
		$mail->SMTPAuth = true;
		$mail->Username = $this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"];
		$mail->Password = $this->TEST_OBJ->SMTP_OPTIONS["smtp_password"];
		if ($this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"]) {
			$mail->SMTPSecure = $this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"];
		}

		$mail->From		= $this->TEST_OBJ->TEST_MAIL["email_from"];
		if ($this->TEST_OBJ->TEST_MAIL["name_from"]) {
			$mail->FromName = $this->TEST_OBJ->TEST_MAIL["name_from"];
		}
		$mail->AddAddress($this->TEST_OBJ->TEST_MAIL["email_to"], $this->TEST_OBJ->TEST_MAIL["name_to"]);
		$mail->IsHTML(true);

		$mail->Subject = $this->TEST_OBJ->TEST_MAIL["subject"];
		$mail->Body    = $this->TEST_OBJ->TEST_MAIL["html"];
		$mail->AltBody = $this->TEST_OBJ->TEST_MAIL["text"];

		// Go!
		$result = $mail->Send();
		$error_message .= $mail->ErrorInfo;

		$body .= $result ? "<b style='color:green;'>Send successful</b>" : "<b style='color:red;'>Send failed</b>";
		$body .= !$result ? "<br /><b>Reason:</b><br /> ".$error_message.implode("<br />\n", (array)main()->_all_core_error_msgs)."<br />" : "";
		$body .= "<br />Spent time: ".common()->_format_time_value(microtime(true) - $time_start)." sec.<br />";
		return $body;
	}
}
