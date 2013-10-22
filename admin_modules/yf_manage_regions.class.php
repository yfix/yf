<?php

/**
* Regions management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_regions {

// TODO

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('regions')/*, array('id' => 'code')*/)
			->text('name')
			->text('country')
			->text('code')
			->text('code3')
			->text('num')
			->text('cont')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db('regions').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_update_if_ok('regions', array('name','active'), 'id='.$a['id'], array('on_after_update' => function() {
				cache()->refresh(array('regions'));
				common()->admin_wall_add(array('region updated: '.$_POST['name'].'', $a['id']));
			}))
			->text('name')
			->text('country')
			->info('code')
			->info('code3')
			->info('num')
			->info('cont')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(array('name' => 'trim|required'))
			->db_insert_if_ok('regions', array('name','active'), array(), array('on_after_update' => function() {
				cache()->refresh(array('regions'));
				common()->admin_wall_add(array('region added: '.$_POST['name'].'', db()->insert_id()));
			}))
			->text('name')
			->text('country')
			->info('code')
			->info('code3')
			->info('num')
			->info('cont')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => db('regions')));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => db('regions')));
	}

	/**
	*/
	function _hook_widget__regions_list ($params = array()) {
// TODO
	}
}
