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
		return table('SELECT * FROM '.db('admin_walls').' WHERE user_id='.intval(main()->ADMIN_ID).' ORDER BY add_date DESC')
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
