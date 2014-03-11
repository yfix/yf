<?php

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
				_re('Login or Email required');
			}
			// Check if user with such login exists
			if (!empty($_POST['login'])) {
				$A = db()->query_fetch('SELECT id,name,login,password,email,nick FROM '.db('user').' WHERE login="'._es($_POST['login']).'"');
				if (empty($A['id'])) {
					_re('Login was not found', 'login');
				}
				if (!common()->_error_exists()) {
					$result = $this->_send_info_to_user($A);
					if (!$result) {
						_re('Server mail error');
					}
				}
			// Check if user with such email exists
			} elseif (!empty($_POST['email'])) {
				$Q = db()->query('SELECT id,name,login,password,email,nick FROM '.db('user').' WHERE email="'._es($_POST['email']).'"');
				if (!db()->num_rows($Q)) {
					_re('Email was not found', 'email');
				}
				// Check if errors exists and send all found accounts
				if (!common()->_error_exists()) {
					while ($A = db()->fetch_assoc($Q)) {
						$result = $this->_send_info_to_user($A);
						if (!$result) {
							_re('Server mail error');
						}
					}
				}
			}
			if (!common()->_error_exists()) {
				$success_msg = t('Password has been sent to your email address. It should arrive in a couple of minutes.');
			}
		}
		$replace = array(
			'form_action' => './?object='.$_GET['object'],
		);
		$login_form = form($replace, array('legend' => 'Enter your login', 'class' => 'form-vertical'))
			->validate(array('login' => 'trim|required'))
			->text('login', 'Enter your login')
			->submit('', 'Get Password', array('class' => 'btn btn-small'));
		$email_form = form($replace, array('legend' => 'Enter your email', 'class' => 'form-vertical'))
			->validate(array('email' => 'trim|required'))
			->email('email', 'Enter your email')
			->submit('', 'Get Password', array('class' => 'btn btn-small'));
		return tpl()->parse(__CLASS__.'/main', array(
			'error'        => _e(),
			'success'      => !empty($success_msg) ? $success_msg : '',
			'login_form'   => $login_form,
			'email_form'   => $email_form,
		));
	}

	//
	function _send_info_to_user ($user = array()) {
		if (empty($user)) {
			return false;
		}
		$html = tpl()->parse($_GET['object'].'/email', array(
			'user_name'		=> _display_name($user),
			'password'		=> $user['password'],
			'login'			=> $user['login'],
			'advert_name'	=> SITE_ADVERT_NAME,
			'home_url'		=> _force_get_url(array(), '', './'),
			'login_url'		=> _force_get_url(array('object' => 'login_form')),
			'faq_url'		=> _force_get_url(array('object' => 'faq')),
		));
		return common()->send_mail(array(
			'from_mail' => SITE_ADMIN_EMAIL,
			'from_name'	=> SITE_ADVERT_NAME,
			'to_mail'	=> $user['email'],
			'to_name'	=> _display_name($user),
			'subj'		=> t('Password Found'),
			'html'		=> $html,
			'text'		=> nl2br(strip_tags($html)),
// TODO: implement these inside send_mail
			'on_error'	=> function($params) {
				common()->message_error('Server cannot send email to you, please contact support');
			},
// TODO: implement these inside send_mail
			'on_success' => function($params) {
				common()->message_success('Email was sent successfully');
			},
		));
	}

	function _site_title($title){
		return $this->_my_site_title;
	}
}
