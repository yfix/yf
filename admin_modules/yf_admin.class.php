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
		$admin_id = main()->ADMIN_ID;
		$func = function($row) use ($admin_id) {
			return !($row['id'] == $admin_id);
		};
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		return table('SELECT * FROM '.db('admin'), array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
				),
			))
			->text('login')
			->text('email')
			->link('group', './?object=admin_groups&action=edit&id=%d', main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->text('go_after_login')
			->date('add_date')
			->btn_active(array('display_func' => $func))
			->btn_edit()
			->btn_delete(array('display_func' => $func))
			->btn('log_auth', './?object=log_admin_auth&action=show_for_admin&id=%d')
			->btn('login', './?object='.$_GET['object'].'&action=login_as&id=%d', array('display_func' => $func))
			->footer_link('Failed auth log', './?object=log_admin_auth_fails')
			->footer_add();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('Wrong id');
		}
		$admin_id = main()->ADMIN_ID;
		$func = function($row) use ($admin_id) {
			return !($row['id'] == $admin_id);
		};
		$a = db()->get('SELECT * FROM '.db('admin').' WHERE id='.$id);
		$a['back_link'] = './?object='.$_GET['object'];
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_numeric|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'password_update',
				'group'			=> 'required|exists[admin_groups.id]',
			))
			->db_update_if_ok('admin', array('login','email','first_name','last_name','go_after_login','password','group'), 'id='.$id)
			->on_after_update(function() {
				common()->admin_wall_add(array(t('admin account edited: %login', array('%login' => $_POST['login'])), $id));
			})
			->login()
			->email()
			->password(array('value' => ''))
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->info_date('add_date','Added')
			->row_start()
				->save_and_back()
				->link('log auth', './?object=log_admin_auth&action=show_for_admin&id='.$a['id'])
				->link('login as', './?object='.$_GET['object'].'&action=login_as&id='.$a['id'], array('display_func' => $func))
			->row_end()
		;
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
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'required|md5',
				'group'			=> 'required|exists[admin_groups.id]',
			))
			->db_insert_if_ok('admin', array('login','email','first_name','last_name','go_after_login','password','group','active'), array('add_date' => time()))
			->on_after_update(function() {
				common()->admin_wall_add(array('admin account added: '.$_POST['login'].'', main()->ADMIN_ID));
			})
			->login()
			->email()
			->password(array('value' => ''))
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->save_and_back()
		;
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
	function login_as() {
// TODO: move this into classes/auth_admin
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('Wrong id');
		}
		if (main()->ADMIN_GROUP != 1) {
			return _e('Allowed only for super-admins');
		}
		$a = db()->get('SELECT * FROM '.db('admin').' WHERE id='.$id);
		if (!$a) {
			return _e('Target admin user info not found');
		}
		$t_group = db()->get('SELECT * FROM '.db('admin_groups').' WHERE id='.(int)$a['group']);
		// Save previous session
		$tmp = $_SESSION;
		$_SESSION['admin_prev_info'] = $tmp;
		// Login as different admin user
		$_SESSION['admin_id'] = $a['id'];
		$_SESSION['admin_group'] = $a['group'];
		$_SESSION['admin_login_time'] = time();

		$after_login = $t_group['go_after_login'] ?: $t_group['go_after_login'];
		return js_redirect($after_login ?: './');
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'login|email|group|first_name|last_name|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->login('login', array('class' => 'input-medium'))
			->email('email', array('class' => 'input-medium'))
			->select_box('group', main()->get_data('admin_groups'))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_accounts ($params = array()) {
// TODO
	}
}
