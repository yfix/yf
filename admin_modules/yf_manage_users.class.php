<?php

/**
*/
class yf_manage_users {

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		return table('SELECT * FROM '.db('user'), array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
					'name'	=> 'like',
				),
			))
			->text('id')
			->text('login')
			->text('email')
			->text('name')
			->btn_edit()
			->btn_delete()
			->btn_active()
			->btn('log_auth', './?object=log_гыук_auth&action=show_for_user&id=%d')
			->btn('login', './?object='.$_GET['object'].'&action=login_as&id=%d')
			->footer_add()
			->footer_link('Failed auth log', './?object=log_auth_fails_viewer')
		;
// TODO: editing
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'login' => 'trim|required|alpha_numeric|is_unique[user.login]',
				'email' => 'trim|required|valid_email|is_unique[user.email]',
			))
			->db_insert_if_ok('user', array('login','email','name','active'), array('add_date' => time()), array('on_after_update' => function() {
				common()->admin_wall_add(array('user added: '.$_POST['login'].'', db()->insert_id()));
			}))
			->login()
			->email()
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('user').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'login' => 'trim|required|alpha_numeric|is_unique_without[user.login.'.$id.']',
				'email' => 'trim|required|valid_email|is_unique_without[user.email.'.$id.']',
			))
			->db_update_if_ok('user', array('login','email','name','active'), 'id='.$id, array('on_after_update' => function() {
				common()->admin_wall_add(array('user updated: '.$_POST['login'].'', $id));
			}))
			->login()
			->email()
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function active() {
		if (!empty($_GET['id'])) {
			$user_info = user($_GET['id']);
		}
		if (!empty($user_info)) {
			update_user($user_info['id'], array('active' => (int)!$user_info['active']));
		}
		cache()->refresh('user');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($user_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Delete all user account information 
	*/
	function delete() {
		$user_id = intval($_GET['id']);
		if (!$user_id) {
			return false;
		}
		$hook_func_name = '_on_delete_account';

		$_user_modules = module('user_modules')->_get_modules();
		$_user_modules_methods = module('user_modules')->_get_methods(array('private' => 1));
		$_modules_where_exists = array();
		foreach  ((array)$_user_modules_methods as $module_name => $methods) {
			if (!in_array($hook_func_name, $methods)) {
				continue;
			}
			$_modules_where_exists[$module_name] = $module_name;
		}
		foreach ((array)$_modules_where_exists as $_module_name) {
			$m = module($_module_name);
			if (method_exists($m, $hook_func_name)) {
				$result = $m->$hook_func_name(array('user_id' => $user_id));
			}
		}

		$user_info = user($user_id);
		$domains = main()->get_data('domains');
		if ($user_info['login'] && $user_info['domain']) {
			$user_folder_name = $user_info['login'].'.'.$domains[$user_info['domain']];
		}
		if ($user_folder_name) {
			$user_folder_path = INCLUDE_PATH.'users/'.$user_folder_name.'/';
		}
		if ($user_folder_path && file_exists($user_folder_path)) {
			_class('dir')->delete_dir($user_folder_path, true);
		}
		db()->query('DELETE FROM '.db('user').' WHERE id='.$user_id);
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	*/
	function login_as() {
// TODO: move this into classes/auth_user ?
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('Wrong id');
		}
		$a = db()->get('SELECT * FROM '.db('user').' WHERE id='.$id);
		if (!$a) {
			return _e('Target user not found');
		}
		$t = time();
		$secret_key = db()->get_one('SELECT MD5(CONCAT(`password`, "'.str_replace(array('http://', 'https://'), '//', WEB_PATH).'")) FROM '.db('admin').' WHERE id=1');
		$to_encode = 'userid-'.$a['id'].'-'.$t.'-'.md5($a['password']);
		$integrity_hash = md5($to_encode);
		$encrypted = _class('encryption')->_safe_encrypt_with_base64($to_encode.'-'.$integrity_hash, $secret_key);
		if (tpl()->REWRITE_MODE) {
#			$url = WEB_PATH.'login/'.$encrypted;
			$url = _force_get_url(array('task' => 'login', 'id' => $encrypted), parse_url(WEB_PATH, PHP_URL_HOST));
		} else {
			$url = WEB_PATH.'?task=login&id='.$encrypted;
		}
		return js_redirect($url, $rewrite = false);
	}

	/**
	* User account confirmation
	*/
	function do_confirm () {
// TODO
/*
		if (!strlen($_POST['login'])) {
			_re(t('Login required'));
		}
		if (!common()->_error_exists()) {
			$A = db()->query_fetch('SELECT * FROM '.db('user').' WHERE active='0' AND login="'._es($_POST['login']).'"');
			if (!$A['id']) _re(t('Sorry, either someone has already confirmed membership or some important information has been missed. Please enter email below and submit'));
		}
		// Continue if check passed
		if (!common()->_error_exists()) {
			// Send email to the confirmed user
			$replace2 = array(
				'name'		=> _display_name($A),
				'email'		=> $A['email'],
				'password'	=> $A['password'],
			);
			$message = tpl()->parse($_GET['object'].'/email', $replace2);
			// Set user confirmed
			db()->query('UPDATE '.db('user').' SET active='1' WHERE id='.intval($A['id']));
			common()->send_mail(SITE_ADVERT_NAME, SITE_ADMIN_EMAIL, $A['email'], _display_name($A), 'Thank you for registering with us!', $message, nl2br($message));
			$replace = array(
				'name'	=> _display_name($A),
			);
			$body = tpl()->parse($_GET['object'].'/confirmed', $replace);
		} else {
			$body .= _e();
			$body .= $this->show($_POST);
		}
		return $body;
*/
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
		foreach (explode('|', 'name,login,email|add_date|last_login|num_logins|active') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('id')
			->text('name')
			->login('login')
			->email('email')
			->select_box('group', main()->get_data('user_groups'), array('show_text' => 1))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__members_stats ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__members_latest ($params = array()) {
// TODO
	}
}
