<?php

/**
* Users "log in" info analyser
*/
class yf_log_user_auth {

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_auth');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->user('user_id')
			->text('login')
			->link('group', './?object=user_groups&action=edit&id=%d', main()->get_data('user_groups'))
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->date('date', 'full')
			->text('user_agent')
			->text('referer')
		;
	}

	/**
	* Show log logins for selected user
	*/
	function show_for_user () {
		$_GET['page'] = 'clear';
		$_GET['filter'] = 'user_id:'.intval($_GET['id']);
		return $this->filter_save();
	}

	/**
	* Show log logins for selected IP address
	*/
	function show_for_ip () {
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
		foreach (explode('|', 'user_id|login|group|date|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('user_id')
			->text('login')
			->text('ip')
			->select_box('group', main()->get_data('user_groups'), array('show_text' => 1))
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__user_auth_log ($params = array()) {
// TODO
	}
}
