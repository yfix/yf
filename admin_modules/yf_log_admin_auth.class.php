<?php

/**
* Admin "log in" info analyser
*/
class yf_log_admin_auth {

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_admin_auth');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->admin('admin_id')
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->date('date', 'full')
			->text('user_agent')
			->text('referer')
		;
	}

	/**
	*/
	function show_for_admin() {
		$_GET['page'] = 'clear';
		$_GET['filter'] = 'admin_id:'.intval($_GET['id']);
		return $this->filter_save();
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
		foreach (explode('|', 'admin_id|login|group|date|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('admin_id')
			->text('ip')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_auth_successes ($params = array()) {
// TODO
	}
}
