<?php

/**
* Languages management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_languages {

	/**
	*/
	private $params = array(
		'table' => 'languages',
		'id'	=> 'code',
	);

	/**
	*/
	function show() {
// TODO
		return table('SELECT * FROM '.db('languages'), array('id' => 'code'))
			->text('name')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		$a = db()->query_fetch('SELECT * FROM '.db('languages').' WHERE code="'._es($_GET['id']).'"');
		if (!$a) {
			return _e('Wrong record!');
		}
		$a['id'] = $a['code'];
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('name' => 'trim|required|alpha-dash'))
			->db_update_if_ok('languages', array('name','active'), 'code="'._es($a['code']).'"', array('on_after_update' => function() {
				cache()->refresh(array('languages'));
				common()->admin_wall_add(array('language updated: '.$_POST['name'].'', $a['code']));
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
		return _class('admin_methods')->delete($this->params);
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->params);
	}

	/**
	*/
	function _hook_widget__languages_list ($params = array()) {
// TODO
	}
}
