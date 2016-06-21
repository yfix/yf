<?php

/**
* Log authentification fails viewer
*/
class yf_log_admin_auth_fails {

	private $_reasons = [
		'w' => 'Wrong login',
		'b' => 'Blocked',
	];

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = [
			'order_by' => 'time',
			'order_direction' => 'desc',
		];
		$sql = 'SELECT * FROM '.db('log_admin_auth_fails');
		return table($sql, [
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => [
					'name'	=> 'like',
				],
			])
			->text('reason', ['data' => $this->_reasons])
			->date('time', ['format' => 'full', 'nowrap' => 1])
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->text('login')
			->text('pswd')
			->text('user_agent')
			->text('referer')
		;
	}

	/**
	*/
	function show_for_ip() {
		$_GET['page'] = 'clear';
		$_GET['filter'] = 'ip:'.preg_replace('~[^0-9\.]+~ims', '', $_GET['id']);
		return $this->filter_save();
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
		foreach (explode('|', 'login|time|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, [
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			])
			->text('login')
			->text('ip')
			->select_box('reason', $this->_reasons, ['show_text' => 1])
			->select_box('order_by', $order_fields, ['show_text' => 1])
			->order_box()
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_auth_fails ($params = []) {
// TODO
	}
}
