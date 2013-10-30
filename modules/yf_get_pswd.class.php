<?php

//-----------------------------------------------------------------------------
// Class for handling retrieving lost password
class yf_get_pswd {


	function _init(){
		$this->_my_site_title = t('Password Reminder');
	}
	
	/*
	* Default function
	*/
	function show () {
		conf('_login_form_displayed', true);
		if(!empty($_POST)){
			if (empty($_POST['login']) && empty($_POST['email'])) {
				_re(t("Login or Email required"));
			}
			// Check if user with such login exists
			if (!empty($_POST['login'])) {
				$A = db()->query_fetch("SELECT id,name,login,password,email,nick FROM ".db('user')." WHERE login='"._es($_POST['login'])."'");
				if (empty($A["id"])) {
					_re(t("Login was not found"), "login");
				}
				if (!common()->_error_exists()) {
					$result = $this->_send_info_to_user($A);
					if (!$result) {
						_re(t("Server mail error"));
					}
				}
			// Check if user with such email exists
			} elseif (!empty($_POST['email'])) {
				$Q = db()->query("SELECT id,name,login,password,email,nick FROM ".db('user')." WHERE email='"._es($_POST['email'])."'");
				if (!db()->num_rows($Q)) {
					_re(t("Email was not found"), "email");
				}
				// Check if errors exists and send all found accounts
				if (!common()->_error_exists()) {
					while ($A = db()->fetch_assoc($Q)) {
						$result = $this->_send_info_to_user($A);
						if (!$result) {
							_re(t("Server mail error"));
						}
					}
				}
			}
			if (!common()->_error_exists()) {
				$success_msg = t("Password has been sent to your email address. It should arrive in a couple of minutes.");
			}
		}
		$replace = array(
			"form_action" => "./?object=".$_GET["object"],
		);
		$login_form = common()->form2($replace, array('legend' => 'Enter your login', 'class' => 'form-vertical'))
			->validate(array('login' => 'trim|required',))
			->text('login', 'Enter your login')
			->submit('', 'Get Password', array('class' => 'btn btn-small'));
		$email_form = common()->form2($replace, array('legend' => 'Enter your email', 'class' => 'form-vertical'))
			->validate(array('email' => 'trim|required',))
			->email('email', 'Enter your email')
			->submit('', 'Get Password', array('class' => 'btn btn-small'));
		return tpl()->parse(__CLASS__."/main", array(
			'error'        => _e(),
			'success'      => !empty($success_msg) ? $success_msg : "",
			'login_form'   => $login_form,
			'email_form'   => $email_form,
		));
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

	function _site_title($title){
		return $this->_my_site_title;
	}
}
