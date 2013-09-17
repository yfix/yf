<?php

/*
* Admin account
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_account {

	function show() {
		$validate_rules = array(
#			'__all__'		=> 'trim',
			'email'			=> 'required|valid_email|is_unique_without[user.email.'.main()->ADMIN_ID.']',
			'first_name'	=> 'required|alpha_numeric_spaces',
			'last_name'		=> 'required|alpha_numeric_spaces',
# TODO: finish this
#			'password'		=> 'password_update',
#			'go_after_login'=> '',
		);
		$a = db()->query_fetch('SELECT * FROM '.db('admin').' WHERE id='.(int)main()->ADMIN_ID);
		$on_before_update_func = function(&$data, &$table, &$fields, &$type, &$extra) {
			$fname = 'password';
			$posted = trim($_POST[$fname]);
			if ($fields[$fname]) {
				if (!strlen($posted)) {
					unset($fields[$fname]);
				} else {
					$data[$fname] = md5($posted);
#					common()->set_notice('Password updated');
				}
			}
		};
		return form($a)
			->validate($validate_rules)
			->db_update_if_ok('admin', array('email','first_name','last_name','go_after_login','password'), 'id='.(int)main()->ADMIN_ID, array('on_before_update' => $on_before_update_func))
			->info('login')
			->info('group', array('data' => main()->get_data('admin_groups')))
			->password('password', array('value' => ''))
			->email('email')
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->save();
	}
}
