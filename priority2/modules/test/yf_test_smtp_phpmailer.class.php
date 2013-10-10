<?php

/**
* Test sub-class
*/
class yf_test_smtp_phpmailer {

	/**
	* test PHPMailer
	*/
	function test () {
		$time_start = microtime(true);

		require_once(YF_PATH."libs/phpmailer/class.phpmailer.php");

		$mail = new PHPMailer();

		$mail->IsSMTP();
		$mail->Host		= module('test')->SMTP_OPTIONS["smtp_host"];
		$mail->SMTPAuth = true;
		$mail->Username = module('test')->SMTP_OPTIONS["smtp_user_name"];
		$mail->Password = module('test')->SMTP_OPTIONS["smtp_password"];
		if (module('test')->SMTP_OPTIONS["smtp_secure"]) {
			$mail->SMTPSecure = module('test')->SMTP_OPTIONS["smtp_secure"];
		}

		$mail->From		= module('test')->TEST_MAIL["email_from"];
		if (module('test')->TEST_MAIL["name_from"]) {
			$mail->FromName = module('test')->TEST_MAIL["name_from"];
		}
		$mail->AddAddress(module('test')->TEST_MAIL["email_to"], module('test')->TEST_MAIL["name_to"]);
		$mail->IsHTML(true);

		$mail->Subject = module('test')->TEST_MAIL["subject"];
		$mail->Body	= module('test')->TEST_MAIL["html"];
		$mail->AltBody = module('test')->TEST_MAIL["text"];

		// Go!
		$result = $mail->Send();
		$error_message .= $mail->ErrorInfo;

		$body .= $result ? "<b style='color:green;'>Send successful</b>" : "<b style='color:red;'>Send failed</b>";
		$body .= !$result ? "<br /><b>Reason:</b><br /> ".$error_message.implode("<br />\n", (array)main()->_all_core_error_msgs)."<br />" : "";
		$body .= "<br />Spent time: ".common()->_format_time_value(microtime(true) - $time_start)." sec.<br />";
		return $body;
	}
}
