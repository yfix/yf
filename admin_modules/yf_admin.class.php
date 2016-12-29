<?php

/**
* Admin users manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin {

	const table = 'admin';

	/**
	*/
	function _show_quick_filter () {
		$filter = _class('admin_methods')->_get_filter();
		foreach ((array)$filter as $k => $v) {
			$a[] = '<small class="text-info">'.$k.':'.$v.'</small>&nbsp;';
		}
		$a[] = a('/@object/filter_save/clear/', 'Clear filter', 'fa fa-close', '', '', '');
		return $a ? '<div class="pull-right">'.implode(PHP_EOL, $a).'</div>' : '';
	}

	/**
	*/
	function show() {
		$admin_id = main()->ADMIN_ID;
		$func = function($row) use ($admin_id) {
			return !($row['id'] == $admin_id);
		};
		$quick_filter = $this->_show_quick_filter();
		return 
			'<div class="col-md-12">' .
			( $quick_filter ? '<div class="col-md-6 pull-right" title="'.t('Quick filter').'">'.$quick_filter.'</div>' : '') .
			'</div>' .
			table(from(self::table), [
				'filter' => true,
				'filter_params' => [
					'login'	=> 'like',
					'email'	=> 'like',
				],
				'hide_empty' => true,
				'custom_fields' => [
					'auths' => (string)select('admin_id', 'COUNT(*)')->from('sys_log_admin_auth')->group_by('admin_id')->where_raw('admin_id IN(%ids)'),
					'visits' => (string)select('admin_id', 'COUNT(*)')->from('sys_log_admin_exec')->group_by('admin_id')->where_raw('admin_id IN(%ids)'),
					'last_visit' => (string)select('admin_id', 'MAX(`date`)')->from('sys_log_admin_exec')->group_by('admin_id')->where_raw('admin_id IN(%ids)'),
					'revisions' => (string)select('user_id', 'COUNT(*)')->from('sys_revisions')->group_by('user_id')->where_raw('user_id IN(%ids)'),
					'wall' => (string)select('user_id', 'COUNT(*)')->from('admin_walls')->group_by('user_id')->where_raw('user_id IN(%ids)'),
				],
			])
			->on_before_render(function($p, $data, $table) {
				$table->data_admin_groups = main()->get_data('admin_groups');
			})
			->func('group', function($gid,$e,$a,$p,$t) {
				return '<b class="text-info">'.$a['login'].'</b>&nbsp;<small><br><i>'.$a['email'].'</i></small><br>'.
					$a['first_name'].'&nbsp;'.$a['last_name'].'&nbsp;'. 
					'<div class="pull-right">'.
					a('/admin_groups/edit/'.$gid, $t->data_admin_groups[$gid], 'fa fa-users').
					a('/@object/filter_save/clear/?filter=group:'.$gid, '', 'fa fa-filter', '').
					'</div>';
			}, ['desc' => 'Info'])
			->func('id', function($v,$e,$a) {
				return $a['visits'] ? a('/log_admin_exec/show_for_admin/'.$a['id'], (int)$a['visits'], 'fa fa-filter') : '';
			}, ['desc' => 'Visits'])
			->func('id', function($v,$e,$a) {
				return $a['auths'] ? a('/log_admin_auth/show_for_admin/'.$a['id'], (int)$a['auths'], 'fa fa-filter') : '';
			}, ['desc' => 'Auths'])
			->func('id', function($v,$e,$a) {
				return $a['revisions'] ? a('/log_admin_revisions/show_for_admin/'.$a['id'], (int)$a['revisions'], 'fa fa-filter') : '';
			}, ['desc' => 'Revisions'])
			->func('id', function($v,$e,$a) {
				return $a['wall'] ? a('/log_admin_wall/show_for_admin/'.$a['id'], (int)$a['wall'], 'fa fa-filter') : '';
			}, ['desc' => 'Wall'])
			->text('go_after_login')
			->func('add_date', function($v,$e,$a){
				$a['last_visit'] && $out[] = '<span title="'.t('Last visit date').'"><i class="fa fa-eye"></i>&nbsp;'._format_date($a['last_visit'], 'full').'</span>';
				$a['last_login'] && $out[] = '<span title="'.t('Last login date').'"><i class="fa fa-sign-in"></i>&nbsp;'._format_date($a['last_login'], 'full').'</span>';
				$a['add_date'] && $out[] = '<span title="'.t('Account add date').'"><i class="fa fa-plus"></i>&nbsp;'._format_date($a['add_date'], 'full').'</span>';
				return $out ? '<small>'.implode('<br />'.PHP_EOL, $out).'</small>' : null;
			}, ['desc' => 'Dates'])
			->btn_edit(['no_ajax' => 1, 'btn_no_text' => 1])
			->btn_delete(['display_func' => $func, 'btn_no_text' => 1])
			->btn_active(['display_func' => $func, 'btn_no_text' => 1, 'short' => 1])
			->btn('login as', url('/@object/login_as/%d'), ['display_func' => $func, 'no_ajax' => 1, 'icon' => 'fa fa-sign-in', 'btn_no_text' => 1, 'class_add' => 'btn-info'])
			->footer_add(['no_ajax' => 1, 'class_add' => 'btn-primary'])
			->footer_link('Auth log', url('/log_admin_auth'))
			->footer_link('Auth fails log', url('/log_admin_auth_fails'))
		;
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
		$a = from(self::table)->whereid($id)->get();
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
			->db_update_if_ok(self::table, ['login','email','first_name','last_name','go_after_login','password','group'], 'id='.$id)
			->on_after_update(function() {
				common()->admin_wall_add([t('admin account edited: %login', ['%login' => $_POST['login']]), $id]);
			})
			->login()
			->email()
			->password()
			->text('first_name')
			->text('last_name')
			->select_box('group', main()->get_data('admin_groups'), ['selected' => $a['group']])
			->text('go_after_login', 'Url after login')
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
			->db_insert_if_ok(self::table, ['login','email','first_name','last_name','go_after_login','password','group','active'], ['add_date' => time()])
			->on_after_update(function() {
				common()->admin_wall_add(['admin account added: '.$_POST['login'].'', main()->ADMIN_ID]);
			})
			->login()
			->email()
			->password(['value' => ''])
			->text('first_name')
			->text('last_name')
			->select_box('group', main()->get_data('admin_groups'), ['selected' => $a['group']])
			->text('go_after_login', 'Url after login')
			->active_box()
			->save_and_back()
		;
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		if ($id && $id != 1 && $id != main()->ADMIN_ID) {
			db()->delete(self::table, $id);
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
			$a = from(self::table)->whereid($id)->get();
		}
		if (!empty($a['id']) && $id != 1 && $id != main()->ADMIN_ID) {
			db()->update_safe(self::table, ['active' => (int)!$a['active']], $id);
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
}
