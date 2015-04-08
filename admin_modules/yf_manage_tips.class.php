<?php

class yf_manage_tips {

	/**
	*/
	function show() {
		$data = db()->from('tips')->order_by('name ASC, locale ASC')->get_all();
		return table($data, array(
				'pager_records_on_page' => 1000,
				'group_by' => 'name',
			))
			->text('name')
			->lang('locale')
			->text('text')
			->btn_edit(array('no_ajax' => 1, 'btn_no_text' => 1))
			->btn_delete(array('btn_no_text' => 1))
			->btn_active()
			->header_add();
	}

	/**
	*/
	function add() {
		$replace = _class('admin_methods')->add(array('table' => 'tips'));
		return form($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$replace = _class('admin_methods')->edit(array('table' => 'tips'));
		return form($replace)
			->text('name')
			->textarea('text')
			->select_box('locale', main()->get_data('languages'))
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(array('table' => 'tips'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'tips'));
	}

	/**
	*/
	function clone_item() {
		return _class('admin_methods')->clone_item(array('table' => 'tips'));
	}
}
