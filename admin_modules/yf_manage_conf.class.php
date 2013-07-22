<?php

class yf_manage_conf {

	/**
	*/
	function _init() {
		$this->_table = array(
			'table'		=> db('conf'),
			'fields'	=> array(
				'name', 'value', 'desc'
			),
			'id'		=> 'name',
		);
	}

	/**
	*/
	function show() {
		return common()->table2('SELECT * FROM '.db('conf').' ORDER BY `name` ASC', array(
				'sortable' => 1,
				'id' => 'name'
			))
			->text('name')
			->text('value')
			->btn_edit()
			->btn_delete()
			->btn_clone()
			->footer_add();
	}

	/**
	*/
	function add() {
		$replace = _class('admin_methods')->add($this->_table);
		return common()->form2($replace)
			->text('name')
			->text('value')
			->textarea('desc')
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$replace = _class('admin_methods')->edit($this->_table);
		return common()->form2($replace)
			->text('name')
			->text('value')
			->textarea('desc')
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete($this->_table);
	}

	/**
	*/
	function clone_item() {
		return _class('admin_methods')->clone_item($this->_table);
	}
}