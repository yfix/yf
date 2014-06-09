<?php

/*
* Admin account
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_account {

	/**
	*/
	function show() {
		$a = db()->get('SELECT * FROM '.db('admin').' WHERE id='.(int)main()->ADMIN_ID);
		$a['password'] = '';
		return form($a + (array)$_POST, array('autocomplete' => 'off'))
			->validate(array(
				'__before__'	=> 'trim',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.main()->ADMIN_ID.']',
				'first_name'	=> 'required|alpha_numeric_spaces',
				'last_name'		=> 'required|alpha_numeric_spaces',
				'password'		=> 'password_update',
			))
			->db_update_if_ok('admin', array('email','first_name','last_name','go_after_login','password'), 'id='.(int)main()->ADMIN_ID, array('on_after_update' => function() {
				common()->admin_wall_add(array('admin account details updated', main()->ADMIN_ID));
			}))
			->info('login')
			->info('group', array('data' => main()->get_data('admin_groups')))
			->password(array('value' => ''))
			->email()
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->save();
	}
}
