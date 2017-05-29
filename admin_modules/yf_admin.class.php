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
	function _init () {
		$admin_id = main()->ADMIN_ID;
		$this->display_func = function($a = [], $a2 = []) use ($admin_id) {
			isset($a2['id']) && $a = $a2;
			return !($a['id'] == $admin_id || $a['id'] == 1);
		};
	}

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
			->btn_delete(['display_func' => $this->display_func, 'btn_no_text' => 1])
			->btn_active(['display_func' => $this->display_func, 'btn_no_text' => 1, 'short' => 1])
			->btn('login as', url('/@object/login_as/%d'), ['display_func' => $this->display_func, 'no_ajax' => 1, 'icon' => 'fa fa-sign-in', 'btn_no_text' => 1, 'class_add' => 'btn-info'])
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
		$a = from(self::table)->whereid($id)->get();
		$a['back_link'] = url('/@object');
		$a['redirect_link'] = url('/@object');
		$a['password'] = '';
		$func = $this->display_func;
		$display = $func($a);
		$a = (array)$_POST + $a;
		$up = ['login','email','first_name','last_name','go_after_login','password'];
		if ($display) {
			$up[] = 'group';
			$up[] = 'active';
		}
		return 
			'<div class="col-md-6">'
			. form($a, ['autocomplete' => 'off', 'show_alerts' => 1])
			->validate([
				'__before__'	=> 'trim',
				'login'			=> 'required|alpha_dash_dots|is_unique_without[admin.login.'.$id.']',
				'email'			=> 'required|valid_email|is_unique_without[admin.email.'.$id.']',
				'first_name'	=> 'required',
				'last_name'		=> 'required',
				'password'		=> 'password_update',
				'group'			=> $display ? 'required|exists[admin_groups.id]' : '',
			])
			->db_update_if_ok(self::table, $up)
			->on_after_update(function() use ($a, $id) {
				common()->admin_wall_add([t('admin account edited: %login', ['%login' => $_POST['login']]), $id]);
			})
			->login()
			->email()
			->password()
			->text('first_name')
			->text('last_name')
			->select_box('group', main()->get_data('admin_groups'), ['selected' => $a['group'], 'display_func' => $this->display_func])
			->text('go_after_login', 'Url after login')
			->active_box('', ['display_func' => $this->display_func])
			->save_and_back()
			. '</div>'
			. '<div class="col-md-6">'.$this->ajax($id).'</div>'
		;
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = url('/@object');
		return form($a, ['autocomplete' => 'off', 'show_alerts' => 1])
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
	function ajax ($force_id = false) {
		$id = (int)($force_id ?: $_GET['id']);
		$admin = from(self::table)->whereid($id)->get();
		if (!$id || !$admin) {
			return _404();
		}
		$a = [
			'auths'		=> (int)select('COUNT(*)')->from('sys_log_admin_auth')->group_by('admin_id')->where('admin_id', $id)->one(),
			'visits'	=> (int)select('COUNT(*)')->from('sys_log_admin_exec')->group_by('admin_id')->where('admin_id', $id)->one(),
			'last_visit'=> (int)select('MAX(`date`)')->from('sys_log_admin_exec')->group_by('admin_id')->where('admin_id', $id)->one(),
			'revisions'	=> (int)select('COUNT(*)')->from('sys_revisions')->group_by('user_id')->where('user_id', $id)->one(),
			'wall'		=> (int)select('COUNT(*)')->from('admin_walls')->group_by('user_id')->where('user_id', $id)->one(),
		];
		$info = [];
		$a['auths'] ? $info[] = a('/log_admin_auth/show_for_admin/'.$id, t('Auths'), 'fa fa-sign-in', (int)$a['auths']) : '';
		$a['visits'] ? $info[] = a('/log_admin_exec/show_for_admin/'.$id, t('Visits'), 'fa fa-eye', (int)$a['visits']) : '';
		$a['revisions'] ? $info[] = a('/log_admin_revisions/show_for_admin/'.$id, t('Content revisions'), 'fa fa-file-text', (int)$a['revisions']) : '';
		$a['wall'] ? $info[] = a('/log_admin_wall/show_for_admin/'.$id, t('Wall records'), 'fa fa-asterisk', (int)$a['wall']) : '';

		$format = 'long';
		$dates = [];
		$admin['add_date']		&& $dates[] = html()->icon('fa fa-plus', t('Account add date'), _format_date($admin['add_date'], $format));
		$admin['last_login']	&& $dates[] = html()->icon('fa fa-sign-in', t('Last sign in'), _format_date($admin['last_login'], $format));
		$a['last_visit']		&& $dates[] = html()->icon('fa fa-eye', t('Last visit'), _format_date($a['last_visit'], $format));

		$pair_active = str_replace('class="', 'disabled class="', main()->get_data('pair_active'));
		$pair_yes_no = str_replace('class="', 'disabled class="', main()->get_data('pair_yes_no'));

		$func = $this->display_func;
		$allowed = $func($admin);

		$actions = [];
		$actions[] = $pair_active[$admin['active']];
		$actions[] = a('/@object/edit/'.$id, t('Edit'), 'fa fa-edit', '', 'no-popover');
		$allowed && $actions[] = a('/@object/login_as/'.$id, t('Login as'), 'fa fa-sign-in', '', 'no-popover');

		$visits_chart = '';
		if (!is_ajax()) {
			$daily_visits = $this->_get_admin_daily_visits($id);
			if ($daily_visits) {
				$visits_chart = _class('charts')->jquery_sparklines($daily_visits[$id]);
			}
		}
		$auths_chart = '';
		if (!is_ajax()) {
			$daily_auths = $this->_get_admin_daily_auths($id);
			if ($daily_auths) {
				$auths_chart = _class('charts')->jquery_sparklines($daily_auths[$id]);
			}
		}

		$online_admin_ids = (array)redis()->smembers('online_by_admin');
		$online_last_time = (int)(redis()->hget('online_by_admin_last', $id) / 100);
		$online_minutes_ago = floor((time() - $online_last_time) / 60);
		$online_sockets = array_map('stripslashes', (array)redis()->smembers('socket_by_admin:'.$id));
		$is_online = in_array($id, $online_admin_ids) && $online_minutes_ago <= 30 && $online_sockets;
		if ($is_online) {
			$online_info = '<small class="text-success"><b>'
				.a('#', t('Last visit time').': '. date('Y-m-d H:i:s', $online_last_time), 'fa fa-clock-o', $online_minutes_ago.' '.t('minutes ago'), 'btn-success').' '
				.a('#', t('Websockets'), 'fa fa-link', count($online_sockets), '')
			.'</b></small>';
		}

		$gid = $admin['group'];
		$admin_groups = main()->get_data('admin_groups');
		$group_html = a('/admin_groups/edit/'.$gid, $admin_groups[$gid], 'fa fa-users').
			a('/@object/filter_save/clear/?filter=group:'.$gid, '', 'fa fa-filter', '');

		$data = [
			'is_online'	=> $is_online ? $online_info : '',
			'id'		=> $admin['id']. '&nbsp;'. implode(PHP_EOL, $actions),
			'login'		=> _prepare_html($admin['login']),
			'name'		=> _prepare_html($admin['first_name'].'&nbsp;'.$admin['last_name']),
			'email'		=> _prepare_html($admin['email']),
			'group'		=> $group_html,
			'info'		=> implode(PHP_EOL, $info),
			'dates'		=> $dates ? '<small>'.implode(PHP_EOL.'<br />', $dates).'</small>' : '',
			'visits'	=> $visits_chart,
			'auths'		=> $auths_chart,
		];
		foreach ((array)$data as $k => $v) {
			if (!strlen($v)) {
				unset($data[$k]);
			}
		}
		$body = html()->simple_table($data, [
			'condensed' => 1,
			'val' => [
				'func' => function($in) { return $in ?: false; },
			],
		]);
		if ($force_id) {
			return $body;
		}
		print is_ajax() ? $body : common()->show_empty_page($body);
	}

	/**
	*/
	function _get_admin_daily_visits($admin_ids = []) {
		$days = 60;
		$min_time = time() - $days * 86400;
		$sql = select('admin_id', 'FROM_UNIXTIME(`date`, "%Y-%m-%d") AS d', 'COUNT(*) AS c')
			->from('sys_log_admin_exec')
			->whereid($admin_ids, 'admin_id')
			->where('`date`', '>', $min_time)
			->group_by('admin_id', 'FROM_UNIXTIME(`date`, "%Y-%m-%d")');
		return $this->_get_admin_daily_info($sql, $days, $admin_ids);
	}

	/**
	*/
	function _get_admin_daily_auths($admin_ids = []) {
		$days = 60;
		$min_time = time() - $days * 86400;
		$sql = select('admin_id', 'FROM_UNIXTIME(`date`, "%Y-%m-%d") AS d', 'COUNT(*) AS c')
			->from('sys_log_admin_auth')
			->whereid($admin_ids, 'admin_id')->where('`date`', '>', $min_time)
			->group_by('admin_id', 'FROM_UNIXTIME(`date`, "%Y-%m-%d")');
		return $this->_get_admin_daily_info($sql, $days, $admin_ids);
	}

	/**
	*/
	function _get_admin_daily_info($sql, $days = 60, $admin_ids = []) {
		if (!$admin_ids) {
			return false;
		}
		if (!is_array($admin_ids)) {
			$admin_ids = [$admin_ids];
		}
		$time = time();
		$days = $days ?: 60;
		$data = [];
		foreach ((array)$sql->all() as $a) {
			$data[$a['admin_id']][$a['d']] = $a['c'];
		}
		if (!$data) {
			return false;
		}
		$dates = [];
		foreach (range($days, 0) as $days_ago) {
			$date = date('Y-m-d', $time - $days_ago * 86400);
			$dates[$date] = $days_ago;
		}
		$out = [];
		foreach ((array)$data as $admin_id => $admin_dates) {
			$out[$admin_id] = [];
			$_data = null;
			foreach ($dates as $date => $days_ago) {
				$_data = $admin_dates[$date];
				// Trim empty values from left side
				if (!$out[$admin_id] && !$_data) {
					continue;
				}
				$out[$admin_id][$date] = $_data;
			}
			// Trim values from the right side too
			foreach (array_reverse($out[$admin_id], $preserve_keys = true) as $k => $v) {
				if ($v) {
					break;
				}
				unset($out[$admin_id][$k]);
			}
		}
		return $out;
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		$id && $a = from(self::table)->whereid($id)->get();
		$func = $this->display_func;
		$allowed = $func($a);
		if ($a['id'] && $allowed) {
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
		$id = (int)$_GET['id'];
		$id && $a = from(self::table)->whereid($id)->get();
		$func = $this->display_func;
		$allowed = $func($a);
		if ($a['id'] && $allowed) {
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
			->login('login', ['class' => 'input-medium', 'no_label' => 1])
			->email('email', ['class' => 'input-medium', 'no_label' => 1])
			->select_box('group', main()->get_data('admin_groups'), ['no_label' => 1, 'show_text' => 1])
			->row_start()
				->select_box('order_by', $order_fields, ['show_text' => '= '.t('Order by').' =', 'desc' => t('Order by')])
				->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
			->row_end()
			->save_and_clear();
		;
	}
}
