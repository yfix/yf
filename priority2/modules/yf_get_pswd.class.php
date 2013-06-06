<?php

//-----------------------------------------------------------------------------
// Class for handling retrieving lost password
class yf_get_pswd {

	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		// Default step value
		if (!strlen($_REQUEST['step'])) {
			$_REQUEST['step'] = 1;
		} else {
			$_REQUEST['step'] = intval($_REQUEST['step']);
		}
		$step = intval($_REQUEST['step']);
		$method_name = "_step_".$step;
        if (method_exists($this, $method_name)) {
			return $this->$method_name();
		}
	}

	//-----------------------------------------------------------------------------
	//
	function _step_1 () {
		$replace = array(
			"form_action" => process_url("./?object=".$_GET["object"]),
		);
		return tpl()->parse($_GET["object"]."/step_1", $replace);
	}

	//-----------------------------------------------------------------------------
	//
	function _step_2 () {
		if (empty($_POST['login']) && empty($_POST['email'])) {
			common()->_raise_error(t("Login or Email required"));
		}
		// Check if user with such login exists
		if (!empty($_POST['login'])) {
			$A = db()->query_fetch("SELECT `id`,`name`,`login`,`password`,`email`,`nick` FROM `".db('user')."` WHERE `login`='"._es($_POST['login'])."'");
			if (empty($A["id"])) {
				common()->_raise_error(t("Login was not found"));
			}
			// Check if errors exists
			if (!common()->_error_exists()) {
				$result = $this->_send_info_to_user($A);
				// Check if email is sent - else show error
				if (!$result) {
					return _e(t("Server mail error"));
				}
			}
			// Check if user with such login exists
		} elseif (!empty($_POST['email'])) {
			$Q = db()->query("SELECT `id`,`name`,`login`,`password`,`email`,`nick` FROM `".db('user')."` WHERE `email`='"._es($_POST['email'])."'");
			if (!db()->num_rows($Q)) {
				common()->_raise_error(t("Email was not found"));
			}
			// Check if errors exists and send all found accounts
			if (!common()->_error_exists()) {
				while ($A = db()->fetch_assoc($Q)) {
					$result = $this->_send_info_to_user($A);
					// Check if email is sent - else show error
					if (!$result) {
						return _e(t("Server mail error"));
					}
				}
			}
		}
		// Show form if some errors occured
		if (common()->_error_exists()) {
			$body .= _e();
			$body .= $this->_step_1();
		} else {
			$body .= tpl()->parse($_GET["object"]."/step_2");
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	//
	function _send_info_to_user ($A = array()) {
		if (empty($A)) {
			return false;
		}
		// Process template
		$replace = array(
			"user_name"		=> _display_name($A),
			"password"		=> $A["password"],
			"login"			=> $A['login'],
			"advert_name"	=> SITE_ADVERT_NAME,
			"home_url"		=> process_url("./"),
			"login_url"		=> process_url("./?object=login_form"),
			"faq_url"		=> process_url("./?object=faq"),
		);	
		// Prepare email
		$message	= tpl()->parse($_GET["object"]."/email", $replace);
		$name_from	= SITE_ADVERT_NAME;
		$email_from	= SITE_ADMIN_EMAIL;
		$email_to	= $A['email'];
		$name_to	= _display_name($A);
    	$subject	= t("Password Found");
		// Send email to the user
		$result		= common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $message, nl2br($message));
		return $result;
	}
}
