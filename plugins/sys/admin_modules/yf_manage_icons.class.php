<?php

/**
* Icons management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_icons {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('icons'), array(
				'filter' => true,
				'filter_params' => array('name' => 'like'),
			))
			->text('name')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db('icons').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_update_if_ok('icons', array('name','active'), 'id='.$a['id'])
			->on_after_update(function() {
				cache_del(array('icons'));
				common()->admin_wall_add(array('icon updated: '.$_POST['name'].'', $a['id']));
			})
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_insert_if_ok('icons', array('name','active'), array())
			->on_after_update(function() {
				cache_del(array('icons'));
				common()->admin_wall_add(array('icon added: '.$_POST['name'].'', db()->insert_id()));
			})
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => 'icons'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'icons'));
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
		$order_fields = array(
			'name' => 'name',
			'active' => 'active',
		);
		$per_page = array('' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000);
		return form($r, array(
				'filter' => true,
			))
			->text('name')
			->select_box('per_page', $per_page, array('class' => 'input-small'))
			->select_box('order_by', $order_fields, array('show_text' => 1, 'class' => 'input-medium'))
			->order_box()
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__icons_list ($params = array()) {
// TODO
	}
}
