<?php

/**
* Countries management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_countries {

	/**
	*/
	private $params = [
		'table' => 'countries',
		'id'	=> 'code',
	];

	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__show';
		return table('SELECT * FROM '.db('countries'), [
				'id' => 'code',
				'filter' => $_SESSION[$filter_name],
				'filter_params' => ['name' => 'like', 'native' => 'like'],
			])
			->text('code')
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
		$a = db()->query_fetch('SELECT * FROM '.db('countries').' WHERE code="'._es($_GET['id']).'"');
		if (!$a) {
			return _e('Wrong record!');
		}
		$a['id'] = $a['code'];
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(['name' => 'trim|required'])
			->db_update_if_ok('countries', ['name','active'], 'code="'._es($a['code']).'"')
			->on_after_update(function() {
				cache_del(['countries']);
				common()->admin_wall_add(['country updated: '.$_POST['name'].'', $a['code']]);
			})
			->info('code')
			->text('name')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(['name' => 'trim|required'])
			->db_insert_if_ok('countries', ['name','code','active'], [])
			->on_after_update(function() {
				cache_del(['countries']);
				common()->admin_wall_add(['country added: '.$_POST['name'].'', db()->insert_id()]);
			})
			->text('code')
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
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], ['show'])) {
			return false;
		}
		$filter_name = $_GET['object'].'__show';
		$r = [
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		];
		$order_fields = [
			'code' => 'code',
			'name' => 'name',
			'active' => 'active',
		];
		$per_page = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000];
		return form($r, [
				'selected'	=> $_SESSION[$filter_name],
				'class' => 'form-vertical',
			])
			->text('name')
			->select_box('per_page', $per_page, ['class' => 'input-small'])
			->select_box('order_by', $order_fields, ['show_text' => 1, 'class' => 'input-medium'])
			->radio_box('order_direction', ['asc'=>'Ascending','desc'=>'Descending'], ['horizontal' => 1, 'translate' => 1])
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__countries_list ($params = []) {
// TODO
	}
}
