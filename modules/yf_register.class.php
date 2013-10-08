<?php

/**
* Users registration
*/
class yf_register {

	/**
	*/
	function show () {
		$validate_rules = array(
			'login'		=> array( 'trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean', function($in){ return module('register')->_login_not_exists($in); } ),
			'email'		=> array( 'trim|required|valid_email|is_unique[user.email]', function($in){ return module('register')->_email_not_exists($in); } ),
			'emailconf'	=> 'trim|required|valid_email|matches[email]',
			'password'	=> 'trim|required', //|md5
			'pswdconf'	=> 'trim|required|matches[password]', // |md5
			'captcha'	=> 'trim|captcha',
		);
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=success';
// TODO: generate confirmation code and send emails
		return form($a, array('legend' => 'Registration'))
			->validate($validate_rules)
			->db_insert_if_ok('user', array('login','email','password'), null, array('on_success_text' => 'Your account was created successfully!'))
			->login()
			->email()
			->email('emailconf')
			->password()
			->password('pswdconf')
			->captcha()
			->save();
	}

	/**
	*/
	function success() {
		return common()->show_notices();
	}

	/**
	*/
	function _login_not_exists($in = '') {
// TODO
		return true;
	}

	/**
	*/
	function _email_not_exists($in = '') {
// TODO
		return true;
	}

	/**
	* Send email with verification code
	*/
	function _send_email_with_code ($code = "", $extra = false) {
		$identify = !empty($extra['identify']) ? $extra['identify'] : $_POST["email"];
		$replace = array(
			"nick"			=> $identify,
			"confirm_code"	=> $code,
			"conf_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
			"aol_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
			"conf_link_aol"	=> process_url("./?object=".$_GET["object"]."&action=confirm_aol&id=".$code),
			"conf_form_url"	=> process_url("./?object=login_form&action=account_inactive"),
			"admin_name"	=> SITE_ADVERT_NAME,
			"advert_url"	=> SITE_ADVERT_URL,
		);
		if(isset($extra['add_replace']) && is_array($extra['add_replace'])){
			$replace = array_merge($replace, $extra['add_replace']);
		}
		$text = tpl()->parse($_GET["object"]."/email_confirm".(!empty($_POST["account_type"]) ? '_'.$_POST["account_type"] : ''), $replace);
		// prepare email data
		$email_from	= SITE_ADMIN_EMAIL;
		$name_from	= SITE_ADVERT_NAME;
		$email_to	= !empty($extra['email']) ? $extra['email'] : $_POST["email"];
		$name_to	= $identify;
		$subject	= !empty($extra['subject']) ? $extra['subject'] : t("Membership confirmation required!");
		return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
	}

	/**
	* Send success email
	*/
	function _send_success_email ($extra = false) {
		$identify = !empty($extra['identify']) ? $extra['identify'] : $_POST["email"];
		$replace = array(
			"code"			=> $code,
			"nick"			=> $identify,
			"password"		=> $_POST["password"],
			"advert_name"	=> SITE_ADVERT_NAME,
			"advert_url"	=> SITE_ADVERT_URL,
		);
		$text = tpl()->parse($_GET["object"]."/email_success".(!empty($_POST["account_type"]) ? '_'.$_POST["account_type"] : ''), $replace);
		// prepare email data
		$email_from	= SITE_ADMIN_EMAIL;
		$name_from	= SITE_ADVERT_NAME;
		$email_to	= !empty($extra['email']) ? $extra['email'] : $_POST["email"];
		$name_to	= $identify;
		$subject	= !empty($extra['subject']) ? $extra['subject'] : t("Membership confirmation required!");
		return common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
	}

	/**
	* Confirm registration for common users
	*/
// TODO: convert into form()
	function confirm () {
		// Send registration confirmation email
		if (!$this->CONFIRM_REGISTER) {
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "confirm_not_needed"));
		}
		// Check confirmation code
		if (!strlen($_GET["id"])) {
			return _e(t("Confirmation ID is required!"));
		}
		// Decode confirmation number
		list($user_id, $member_date) = explode("wvcn", trim(base64_decode($_GET["id"])));
		$user_id		= intval($user_id);
		$member_date	= intval($member_date);
		// Get target user info
		if (!empty($user_id)) {
			$target_user_info = user($user_id);
		}
		// User id is required
		if (empty($target_user_info["id"])) {
			return _e("Wrong user ID");
		}
		// Check if user already confirmed
		if ($target_user_info["active"]) {
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "already_confirmed"));
		}
		// Check if code is expired
		if (!common()->_error_exists()) {
			if (!empty($member_date) && (time() - $member_date) > $this->CONFIRM_TTL) {
				_re(t("Confirmation code has expired."));
			}
		}
		if (!common()->_error_exists()) {
			// Check whole code
			if ($_GET["id"] != $target_user_info["verify_code"]) {
				_re(t("Wrong confirmation code"));
			}
		}
		if (!common()->_error_exists()) {
			// Do update user's table (confirm account)
			update_user($user_id, array("active"=>1));
			// Display success message
			return tpl()->parse($_GET["object"]."/confirm_messages", array("msg" => "confirm_success"));
		}
		// Display form
		$body .= _e();
		$body .= tpl()->parse($_GET["object"]."/enter_code", $replace3);
		$body .= tpl()->parse($_GET["object"]."/resend_code", $replace4);
		return $body;
	}

	/**
	* Manually enter code
	*/
// TODO: convert into form()
	function enter_code () {
		// Do activate
		if (isset($_POST["confirm_code"])) {
			$_GET["id"] = $_POST["confirm_code"];
			return $this->confirm();
		}
		// Display form
		$replace = array(
		);
		return tpl()->parse($_GET["object"]."/enter_code", $replace);
	}

	/**
	* Re-send confirmation code
	*/
// TODO: convert into form()
	function resend_code () {
		// Process posted form
		if (!empty($_POST["email"])) {
			// Check if such user exists
			$user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE email='"._es($_POST["email"])."'");
			if (empty($user_info)) {
				return _e("No such user");
			}
			// Check if account already activated
			if ($user_info["active"]) {
				return "Your account is already activated.";
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Generate verification code
				$code = base64_encode($user_info["id"] . "wvcn" . time());
				// Update record with new code
				update_user($user_info["id"], array("verify_code" => $code));
				// Prepare email template
				$replace = array(
					"nick"			=> $user_info["nick"],
					"confirm_code"	=> $code,
					"conf_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
					"aol_link"		=> process_url("./?object=".$_GET["object"]."&action=confirm&id=".$code),
					"conf_link_aol"	=> process_url("./?object=".$_GET["object"]."&action=confirm_aol&id=".$code),
					"conf_form_url"	=> process_url("./?object=login_form&action=account_inactive"),
					"admin_name"	=> SITE_ADVERT_NAME,
					"advert_url"	=> SITE_ADVERT_URL,
				);
				//$text = tpl()->parse($_GET["object"]."/email_confirm_".$this->_account_types[$user_info["group"]], $replace);
				$text = tpl()->parse($_GET["object"]."/email_confirm", $replace);
				// Prepare email
				$email_from	= SITE_ADMIN_EMAIL;
				$name_from	= SITE_ADVERT_NAME;
				$email_to	= $user_info["email"];
				$name_to	= $user_info["nick"];
				$subject	= t("Membership confirmation required!");
				$send_result = common()->send_mail($email_from, $name_from, $email_to, $name_to, $subject, $text, nl2br($text));
				// Check result
				if ($send_result) {
					return "Code sent. Please check your email.";
				} else {
					return "Error sending mail. Please contact site admin.";
				}
			}
		}
		// Display form
		$replace = array(
		);
		return tpl()->parse($_GET["object"]."/resend_code", $replace);
	}
}
