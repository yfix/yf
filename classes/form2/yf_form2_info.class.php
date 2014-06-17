<?php

/**
*/
class yf_form2_info {

	/**
	*/
	function user_info($name = '', $desc = '', $extra = array(), $replace = array(), $_this) {
		$name = 'user_name';
		$user_id = $_this->_replace['user_id'];

		$db = ($_this->_params['db'] ?: $extra['db']) ?: db();

		$user_info = $db->get('SELECT login,email,phone,nick,id AS user_name FROM '.$db->_fix_table_name('user').' WHERE id='.intval($user_id));
		$user_name = array();
// TODO: add tpl param
		if ($user_info) {
			if (strlen($user_info['id'])) {
				$user_name[] = $user_info['id'];
			}
			if (strlen($user_info['login'])) {
				$user_name[] = $user_info['login'];
			}
			if (strlen($user_info['email'])) {
				$user_name[] = $user_info['email'];
			} elseif (strlen($user_info['phone'])) {
				$user_name[] = $user_info['phone'];
			} elseif (strlen($user_info['nick'])) {
				$user_name[] = $user_info['nick'];
			}
		}
		$_this->_replace[$name] = implode('; ', $user_name);

		$extra['link'] = './?object=members&action=edit&id='.$user_id;
		return $_this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function admin_info($name = '', $desc = '', $extra = array(), $replace = array(), $_this) {
		$name = 'admin_name';
		$user_id = $_this->_replace['user_id'];

		$db = ($_this->_params['db'] ?: $extra['db']) ?: db();

		$user_info = $db->get('SELECT login,id AS user_name FROM '.$db->_fix_table_name('admin').' WHERE id='.intval($user_id));
// TODO: add tpl param
		$user_name = array();
		if ($user_info) {
			if (strlen($user_info['id'])) {
				$user_name[] = $user_info['id'];
			}
			if (strlen($user_info['login'])) {
				$user_name[] = $user_info['login'];
			}
		}
		$_this->_replace[$name] = implode('; ', $user_name);
		$extra['link'] = './?object=admin&action=edit&id='.$user_id;
		return $_this->info($name, $desc, $extra, $replace);
	}
}
