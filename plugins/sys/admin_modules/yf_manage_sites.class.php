<?php

/**
* Core sites management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_sites {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('sites'))
			->text('name')
			->text('web_path')
			->text('real_path')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db('sites').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array(
				'name' => 'trim|required',
			))
			->db_update_if_ok('sites', array('name','web_path','real_path'), 'id='.$a['id'])
			->on_after_update(function() {
				cache_del(array('sites'));
				common()->admin_wall_add(array('site updated: '.$_POST['name'].'', $a['id']));
			})
			->text('name')
			->text('web_path')
			->text('real_path')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		return form($a)
			->validate(array(
				'name' => 'trim|required',
			))
			->db_insert_if_ok('sites', array('name','web_path','real_path'), array())
			->on_after_update(function() {
				cache_del(array('sites'));
				common()->admin_wall_add(array('site added: '.$_POST['name'].'', db()->insert_id()));
			})
			->text('name')
			->text('web_path')
			->text('real_path')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => 'sites'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'sites'));
	}

	/**
	*/
	function _hook_widget__sites_list ($params = array()) {
// TODO
	}
}
