<?php

class register {
	function show () {
		$validate_rules = array(
			'login'		=> array( 'trim|required|min_length[5]|max_length[12]|xss_clean', function($in){ return ! module('register')->_login_exists($in); } ),
			'email'		=> array( 'trim|required|valid_email|matches[pswdconf]', function($in){ return ! module('register')->_email_exists($in); } ),
			'emailconf'	=> 'trim|required|valid_email',
			'passsword'	=> 'trim|required|matches[pswdconf]|md5',
			'pswdconf'	=> 'trim|required|md5',
			'pswdconf2'	=> 'trim|required|md5',
			'captcha'	=> 'trim|captcha',
		);
		$form = form()
			->validate($validate_rules)
			->db_insert_if_ok('user', array('login','email','password'))
			->login()
			->email()
			->email('emailconf')
			->password()
			->password('pswdconf')
			->captcha()
			->save()
		;
		return $form;
	}

	function _login_exists($in = "") {
// TODO
		return true;
	}

	function _email_exists($in = "") {
// TODO
		return true;
	}
}
