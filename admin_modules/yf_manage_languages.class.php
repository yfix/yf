<?php

/**
* Languages management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_languages {

// TODO

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('languages'))
			->text('name')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db('languages').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_update_if_ok('languages', array('name','active'), 'id='.$a['id'], array('on_after_update' => function() {
				cache()->refresh(array('languages'));
				common()->admin_wall_add(array('language updated: '.$_POST['name'].'', $a['id']));
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
			->db_insert_if_ok('languages', array('name','active'), array(), array('on_after_update' => function() {
				cache()->refresh(array('languages'));
				common()->admin_wall_add(array('language added: '.$_POST['name'].'', db()->insert_id()));
			}))
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => db('languages')));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => db('languages')));
	}

	/**
	*/
	function _hook_widget__languages_list ($params = array()) {
// TODO
	}
}
