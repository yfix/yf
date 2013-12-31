<?php

/**
* Wall
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_wall {

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__'.$_GET['action'];

		$sql = 'SELECT * FROM '.db('admin_walls').' WHERE user_id='.intval(main()->ADMIN_ID).' ORDER BY add_date DESC';
		return table($sql, array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'message'	=> 'like',
				),
			))
			->date('add_date')
			->text('message')
			->text('object')
			->text('action')
			->text('object_id')
			->btn_view()
		;
	}

	/**
	* Proxy between real link and wall contents
	*/
	function view() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$msg = db()->get('SELECT * FROM '.db('admin_walls').' WHERE user_id='.intval(main()->ADMIN_ID).' AND id='.intval($_GET['id']).' LIMIT 1');
		}
		if (!$msg['id']) {
			return _e('Wrong message id');
		}
		$link = '';
		$object = $msg['object'];
		$action = $msg['action'];
		$object_id = $msg['object_id'];
		$module = module($object);
		$hook_name = '_hook_wall_link';
		if (is_object($module) && method_exists($module, $hook_name)) {
			$link = $module->$hook_name($msg);
		}
		if (!$link) {
			$link = './?object='.$object.'&action='.$action.'&id='.$object_id;
		}
		return js_redirect($link);
	}

	/**
	*/
	function filter_save() {
		$filter_name = $_GET['object'].'__view';
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='. str_replace ($_GET['object'].'__', '', $filter_name));
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
		foreach (explode('|', 'add_date|message|object|action|object_id|admin_id') as $v) {
			$order_fields[$v] = $v;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->text('message')
			->text('object')
			->text('action')
			->integer('object_id')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_wall ($params = array()) {
		$meta = array(
			'name' => 'Admin wall',
			'desc' => 'Latest events for admin',
			'configurable' => array(
//				'order_by'	=> array('id','name','active'),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT * FROM '.db('admin_walls').' WHERE user_id='.intval(main()->ADMIN_ID).' ORDER BY add_date DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1))
			->date('add_date')
			->admin('user_id')
			->text('message')
			->btn_view()
		;
	}

}
