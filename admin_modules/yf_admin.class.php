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
		return table('SELECT * FROM '.db('admin'), array(
				'filter' => true,
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
				),
			))
			->text('login')
			->text('email')
			->link('group', url('/admin_groups/edit/%d'), main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->text('go_after_login')
			->date('add_date')
			->btn_active(array('display_func' => $func))
			->btn_edit()
			->btn_delete(array('display_func' => $func))
			->btn('log_auth', url('/log_admin_auth/show_for_admin/%d'))
			->btn('login', url('/@object/login_as/%d'), array('display_func' => $func))
			->footer_link('Failed auth log', url('/log_admin_auth_fails'))
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
		$a = (array)db()->get('SELECT * FROM '.db('admin').' WHERE id='.$id);
		$a['back_link'] = url('/@object');
		$a['redirect_link'] = url('/@object');
		$a['password'] = '';
		$a = (array)$_POST + $a;
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
			->password()
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), array('selected' => $a['group']))
			->active_box()
			->info_date('add_date','Added')
			->row_start()
				->save_and_back()
				->link('log auth', url('/log_admin_auth/show_for_admin/'.$a['id']))
				->link('login as', url('/@object/login_as/'.$a['id']), array('display_func' => $func))
			->row_end()
		;
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = url('/@object');
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
		$id = (int)$_GET['id'];
		if ($id && $id != 1 && $id != main()->ADMIN_ID) {
			db()->delete('admin', $id);
			common()->admin_wall_add(array('admin account deleted', $id));
		}
		if (main()->is_ajax()) {
			main()->NO_GRAPHICS = true;
			echo $id;
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	*/
	function active () {
		$id = intval($_GET['id']);
		if (!empty($id)) {
			$a = db()->from('admin')->whereid($id)->get();
		}
		if (!empty($a['id']) && $id != 1 && $id != main()->ADMIN_ID) {
			db()->update_safe('admin', array('active' => (int)!$a['active']), $id);
			common()->admin_wall_add(array('admin account '.($a['active'] ? 'inactivated' : 'activated'), $id));
		}
		if (main()->is_ajax()) {
			main()->NO_GRAPHICS = true;
			echo (int)(!$a['active']);
		} else {
			return js_redirect(url('/@object'));
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
		$order_fields = array();
		foreach (explode('|', 'login|email|group|first_name|last_name|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'filter' => true,
			))
			->login('login', array('class' => 'input-medium'))
			->email('email', array('class' => 'input-medium'))
			->select_box('group', main()->get_data('admin_groups'))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->order_box()
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_accounts ($params = array()) {
// TODO
	}
}
