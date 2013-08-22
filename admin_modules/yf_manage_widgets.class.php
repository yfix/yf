<?php

/**
* Widgets management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_widgets {

	/**
	* Framework constructor
	*/
	function _init () {
	}

	/**
	*/
	function show() {
		$sql = 'SELECT * FROM '.db('widgets');
		return table($sql)
			->text('object')
			->text('action')
			->text('theme')
			->text('comments')
			->text('site_ids')
			->text('server_ids')
			->btn_view()
			->btn_edit()
			->btn_clone()
			->btn_delete()
			->btn_active()
			->footer_add()
		;
	}

	/**
	*/
	function add() {
// TODO
	}

	/**
	*/
	function edit() {
// TODO
	}

	/**
	*/
	function delete() {
// TODO
	}

	/**
	*/
	function activate() {
// TODO
	}

	/**
	*/
	function clone_item() {
// TODO
	}

	/**
	*/
	function view() {
// TODO: visual edit here
	}
}
