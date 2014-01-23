<?php

class yf_log_admin_redirects {

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_redirects').' WHERE is_admin="'.strval(!$this->FOR_USER ? 1 : 0).'"';
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'url_from'		=> 'like',
					'url_to'		=> 'like',
					'ip'			=> 'like',
					'user_agent'	=> 'like',
					'referer'		=> 'like',
				),
			))
			->admin('user_id')
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->text('user_agent')
			->text('referer')
			->text('url_from')
			->text('url_to')
			->text('exec_time')
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
		foreach (explode('|', 'user_id|user_group|date|ip|user_agent|referer|url_from|url_to') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('user_id')
			->text('ip')
			->text('user_agent')
			->text('referer')
			->text('url_from')
			->text('url_to')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__redirects_log ($params = array()) {
// TODO
	}

}