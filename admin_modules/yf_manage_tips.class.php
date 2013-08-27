<?php

class yf_manage_tips {

	/**
	*/
	function _init() {
		$this->_table = array(
			'table' => db('tips'),
/*
			'fields' => array(
				'name',
				'text',
				'type',
				'active',
				'locale',
			),
*/
		);
	}

	/**
	*/
	function show() {
		return table2('SELECT * FROM '.db('tips').' ORDER BY `name` ASC')
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
		$replace = _class('admin_methods')->add($this->_table);
		return form2($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$replace = _class('admin_methods')->edit($this->_table);
		return form2($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete($this->_table);
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active($this->_table);
	}

	/**
	*/
	function clone_item() {
		return _class('admin_methods')->clone_item($this->_table);
	}

	/**
	*/
	function sortable() {
		return _class('admin_methods')->sortable($this->_table);
	}

	function _hook_widget__tips ($params = array()) {
// TODO
	}

}