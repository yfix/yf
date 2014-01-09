<?php

/**
* Log authentification fails viewer
*/
class yf_log_user_auth_fails {

	private $_reasons = array(
		'w' => 'Wrong login',
		'b' => 'Blocked',
	);

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'time',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_auth_fails');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->text('reason', array('data' => $this->_reasons))
			->date('time', array('format' => 'full', 'nowrap' => 1))
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
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'login|time|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->text('login')
			->text('ip')
			->select_box('reason', $this->_reasons, array('show_text' => 1))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	function _hook_widget__user_auth_fails ($params = array()) {
// TODO
	}
}
