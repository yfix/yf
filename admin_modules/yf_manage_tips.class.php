<?php

class yf_manage_tips {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('tips').' ORDER BY `name` ASC')
			->text('name','',array('badge' => 'info'))
			->text('text')
			->text('locale')
			->btn_edit()
			->btn_delete()
			->btn_active()
			->footer_add();
	}

	/**
	*/
	function add() {
		$replace = _class('admin_methods')->add(array('table' => db('tips')));
		return form($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$replace = _class('admin_methods')->edit(array('table' => db('tips')));
		return form($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => db('tips')));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => db('tips')));
	}

	/**
	*/
	function clone_item() {
		return _class('admin_methods')->clone_item(array('table' => db('tips')));
	}

	function _hook_widget__tips ($params = array()) {
// TODO
	}

}