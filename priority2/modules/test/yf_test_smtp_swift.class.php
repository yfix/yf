<?php

/**
* Test sub-class
*/
class yf_test_smtp_swift {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	* test EasySwift mailer
	*/
	function run_test () {
		$time_start = microtime(true);

		require_once YF_PATH. "libs/swift/lib/Swift.php";
		require_once YF_PATH. "libs/swift/lib/Swift/Connection/SMTP.php";

		$conn = new Swift_Connection_SMTP($this->TEST_OBJ->SMTP_OPTIONS["smtp_host"], $this->TEST_OBJ->SMTP_OPTIONS["smtp_port"], $this->TEST_OBJ->SMTP_OPTIONS["smtp_secure"] == "tls" ? SWIFT_SMTP_ENC_TLS : false);
		$conn->setUsername($this->TEST_OBJ->SMTP_OPTIONS["smtp_user_name"]);
		$conn->setPassword($this->TEST_OBJ->SMTP_OPTIONS["smtp_password"]);

		$swift	= new Swift($conn);
		$result = $swift->send(
			new Swift_Message($this->TEST_OBJ->TEST_MAIL["subject"]." by swift", $this->TEST_OBJ->TEST_MAIL["text"])
			, new Swift_Address($this->TEST_OBJ->TEST_MAIL["email_from"], $this->TEST_OBJ->TEST_MAIL["name_from"])
			, new Swift_Address($this->TEST_OBJ->TEST_MAIL["email_to"], $this->TEST_OBJ->TEST_MAIL["name_to"])
		);
		$body .= $result ? "<b style='color:green;'>Send successful</b>" : "<b style='color:red;'>Send failed</b>";
		$body .= !$result ? "<br /><b>Reason:</b><br /> ".implode("<br />\n", (array)main()->_all_core_error_msgs)."<br />" : "";
		$body .= "<br />Spent time: ".common()->_format_time_value(microtime(true) - $time_start)." sec.<br />";
		return $body;
	}
}
