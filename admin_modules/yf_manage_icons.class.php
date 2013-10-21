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
		return table('SELECT * FROM '.db('icons'))
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
			->db_update_if_ok('icons', array('name','active'), 'id='.$a['id'], array('on_after_update' => function() {
				cache()->refresh(array('icons'));
				common()->admin_wall_add(array('icon updated: '.$_POST['name'].'', $a['id']));
			}))
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
			->db_insert_if_ok('icons', array('name','active'), array(), array('on_after_update' => function() {
				cache()->refresh(array('icons'));
				common()->admin_wall_add(array('icon added: '.$_POST['name'].'', db()->insert_id()));
			}))
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => db('icons')));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => db('icons')));
	}

	/**
	*/
	function _hook_widget__icons_list ($params = array()) {
// TODO
	}
}
