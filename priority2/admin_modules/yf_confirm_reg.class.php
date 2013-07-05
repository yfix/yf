<?php

//-----------------------------------------------------------------------------
// Confirm user registration
class yf_confirm_reg {

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		$replace = array(
			"form_action"	=> "./?object=".$_GET['object']."&action=do_confirm",
		);
		$body = tpl()->parse($_GET['object']."/main", $replace);
		return $body;
	}

	//-----------------------------------------------------------------------------
	//
	function do_confirm () {
		if (!strlen($_POST["login"]))	_re(t("Login required"));
		if (!common()->_error_exists()) {
			$A = db()->query_fetch("SELECT * FROM `".db('user')."` WHERE `active`='0' AND `login`='"._es($_POST["login"])."'");
			if (!$A["id"]) _re(t("Sorry, either someone has already confirmed membership or some important information has been missed. Please enter email below and submit"));
		}
		// Continue if check passed
		if (!common()->_error_exists()) {
			// Send email to the confirmed user
			$replace2 = array(
				"name"		=> _display_name($A),
				"email"		=> $A["email"],
				"password"	=> $A["password"],
			);
			$message = tpl()->parse($_GET['object']."/email", $replace2);
			// Set user confirmed
			db()->query("UPDATE `".db('user')."` SET `active`='1' WHERE `id`=".intval($A["id"]));
			common()->send_mail(SITE_ADVERT_NAME, SITE_ADMIN_EMAIL, $A["email"], _display_name($A), "Thank you for registering with us!", $message, nl2br($message));
			$replace = array(
				"name"	=> _display_name($A),
			);
			$body = tpl()->parse($_GET['object']."/confirmed", $replace);
		} else {
			$body .= _e();
			$body .= $this->show($_POST);
		}
		return $body;
	}
	/**
	* Page header hook
	*/
	function _show_header() {
		return array(
			"header"	=> "Registration confirmation",
			"subheader"	=> "",
		);
	}
}
//-----------------------------------------------------------------------------
