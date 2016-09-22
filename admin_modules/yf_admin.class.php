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
		return table('SELECT * FROM '.db('admin'), [
				'filter' => true,
				'filter_params' => [
					'login'	=> 'like',
					'email'	=> 'like',
				],
			])
			->text('login')
			->text('email')
			->link('group', url('/admin_groups/edit/%d'), main()->get_data('admin_groups'))
			->text('first_name')
			->text('last_name')
			->text('go_after_login')
			->date('add_date')
			->btn_active(['display_func' => $func])
			->btn_edit()
			->btn_delete(['display_func' => $func])
			->btn('log_auth', url('/log_admin_auth/show_for_admin/%d'))
			->btn('login', url('/@object/login_as/%d'), ['display_func' => $func])
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
		return form($a, ['autocomplete' => 'off'])
			->validate([
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_dash_dots|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'password_update',
				'group'			=> 'required|exists[admin_groups.id]',
			])
			->db_update_if_ok('admin', ['login','email','first_name','last_name','go_after_login','password','group'], 'id='.$id)
			->on_after_update(function() {
				common()->admin_wall_add([t('admin account edited: %login', ['%login' => $_POST['login']]), $id]);
			})
			->login()
			->email()
			->password()
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), ['selected' => $a['group']])
			->active_box()
			->info_date('add_date','Added')
			->row_start()
				->save_and_back()
				->link('log auth', url('/log_admin_auth/show_for_admin/'.$a['id']))
				->link('login as', url('/@object/login_as/'.$a['id']), ['display_func' => $func])
			->row_end()
		;
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = url('/@object');
		return form($a, ['autocomplete' => 'off'])
			->validate([
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_dash_dots|is_unique[admin.login]',
				'email'			=> 'required|valid_email|is_unique[admin.email]',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'required|md5',
				'group'			=> 'required|exists[admin_groups.id]',
			])
			->db_insert_if_ok('admin', ['login','email','first_name','last_name','go_after_login','password','group','active'], ['add_date' => time()])
			->on_after_update(function() {
				common()->admin_wall_add(['admin account added: '.$_POST['login'].'', main()->ADMIN_ID]);
			})
			->login()
			->email()
			->password(['value' => ''])
			->text('first_name')
			->text('last_name')
			->text('go_after_login', 'Url after login')
			->select_box('group', main()->get_data('admin_groups'), ['selected' => $a['group']])
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
			common()->admin_wall_add(['admin account deleted', $id]);
		}
		if (is_ajax()) {
			no_graphics(true);
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
			db()->update_safe('admin', ['active' => (int)!$a['active']], $id);
			common()->admin_wall_add(['admin account '.($a['active'] ? 'inactivated' : 'activated'), $id]);
		}
		if (is_ajax()) {
			no_graphics(true);
			echo (int)(!$a['active']);
		} else {
			return js_redirect(url('/@object'));
		}
	}

	/**
	*/
	function login_as() {
		$id = (int)$_GET['id'];
		if (!$id) {
			return _e('Wrong id');
		}
		if (main()->ADMIN_GROUP != 1) {
			return _e('Allowed only for super-admins');
		}
		return _class('auth_admin', 'classes/auth/')->login_as($id);
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], ['show'])) {
			return false;
		}
		$order_fields = [];
		foreach (explode('|', 'login|email|group|first_name|last_name|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, [
				'filter' => true,
			])
			->login('login', ['class' => 'input-medium'])
			->email('email', ['class' => 'input-medium'])
			->select_box('group', main()->get_data('admin_groups'))
			->select_box('order_by', $order_fields, ['show_text' => 1])
			->order_box()
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_accounts ($params = []) {
// TODO
	}
}
