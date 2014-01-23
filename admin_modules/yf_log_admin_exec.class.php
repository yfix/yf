<?php

class yf_log_admin_exec{

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_admin_exec');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'ip'			=> 'like',
					'user_agent'	=> 'like',
					'referer'		=> 'like',
					'request_uri'	=> 'like',
				),
			))
			->admin('admin_id')
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->text('user_agent')
			->text('referer')
			->text('request_uri')
			->text('exec_time')
			->text('num_dbq')
			->text('page_size')
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
			->text('user_agent')
			->text('referer')
			->text('request_uri')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	function _hook_widget__admin_access_log ($params = array()) {
// TODO
	}

}