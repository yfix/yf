<?php

class register {
	function show () {
		$validate_rules = array(
			'login'		=> array( 'trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean', function($in){ return module('register')->_login_not_exists($in); } ),
			'email'		=> array( 'trim|required|valid_email|is_unique[user.email]', function($in){ return module('register')->_email_not_exists($in); } ),
			'emailconf'	=> 'trim|required|valid_email|matches[email]',
#			'password'	=> 'trim|required', //|md5
			'pswdconf'	=> 'trim|required|matches[password]', // |md5
			'captcha'	=> 'trim|captcha',
		);
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'].'&action=success';
		return form($a)
			->login()
			->email()
			->email('emailconf')
			->password(array('validate' => 'trim|required'))
			->password('pswdconf')
#			->birth()
			->captcha()
			->save()
			->validate($validate_rules)
			->db_insert_if_ok('user', array('login','email','password'), null, array('on_success_text' => 'Your account was created successfully!'))
		;
	}

	function success() {
		return common()->show_notices();
	}

	function _login_not_exists($in = '') {
// TODO
		return true;
	}

	function _email_not_exists($in = '') {
// TODO
		return true;
	}

}
