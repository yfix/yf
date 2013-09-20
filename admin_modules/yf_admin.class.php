<?php

/**
* Admin users manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin {

	/**
	*/
	function show() {
		return common()->table2('SELECT * FROM '.db('admin'))
			->text('login')
			->text('email')
			->link('group', './?object=admin_groups&action=edit&id=%d', main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->text('go_after_login')
			->date('add_date')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->btn('log_auth', './?object=log_admin_auth_view&action=show_for_admin&id=%d')
			->footer_link('Failed auth log', './?object=log_admin_auth_fails_viewer')
			->footer_add();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('Wrong id');
		}
		$a = db()->get('SELECT * FROM '.db('admin').' WHERE id='.$id);
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_numeric|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required|alpha_numeric_spaces',
				'last_name'		=> 'required|alpha_numeric_spaces',
				'password'		=> 'password_update',
				'group'			=> 'required|exists[admin_groups.id]',
			))
			->db_update_if_ok('admin', array('login','email','first_name','last_name','go_after_login','password','group'), 'id='.$id, array(
				'on_after_update' => function() { common()->admin_wall_add(array('admin account edited: '.$_POST['login'].'', $id)); },
			))
			->login()
			->email()
			->password(array('value' => ''))
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->info_date('add_date','Added')
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_numeric|is_unique[admin.login]',
				'email'			=> 'required|valid_email|is_unique[admin.email]',
				'first_name'	=> 'required|alpha_numeric_spaces',
				'last_name'		=> 'required|alpha_numeric_spaces',
				'password'		=> 'required|md5',
				'group'			=> 'required|exists[admin_groups.id]',
			))
			->db_insert_if_ok('admin', array('login','email','first_name','last_name','go_after_login','password','group','active'), array('add_date' => time()), array(
				'on_after_update' => function() { common()->admin_wall_add(array('admin account added: '.$_POST['login'].'', main()->ADMIN_ID)); },
			))
			->login()
			->email()
			->password(array('value' => ''))
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_GET['id'] != 1 && $_GET['id'] != $_SESSION['admin_id']) {
			db()->query('DELETE FROM '.db('admin').' WHERE id='.intval($_GET['id']));
			common()->admin_wall_add(array('admin account deleted', $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']. _add_get());
		}
	}

	/**
	*/
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$admin_info = db()->query_fetch('SELECT * FROM '.db('admin').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($admin_info['id']) && $_GET['id'] != 1 && $_GET['id'] != $_SESSION['admin_id']) {
			db()->UPDATE('admin', array('active' => (int)!$admin_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('admin account '.($admin_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($admin_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function _hook_widget__admin_accounts ($params = array()) {
// TODO
	}
}
