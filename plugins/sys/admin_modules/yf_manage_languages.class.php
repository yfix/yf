<?php

/**
* Languages management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_languages {

	const table = 'languages';

	/**
	*/
	private $params = [
		'table' => 'languages',
		'id'	=> 'code',
	];

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db(self::table), [
				'id' => 'code',
				'filter' => true,
				'filter_params' => ['name' => 'like', 'native' => 'like'],
			])
			->text('code', ['transform' => 'strtoupper'])
			->text('name')
			->text('native')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$_GET['id'] = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		$a = db()->query_fetch('SELECT * FROM '.db(self::table).' WHERE code="'._es($_GET['id']).'"');
		if (!$a) {
			return _e('Wrong record!');
		}
		$a['id'] = $a['code'];
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(['name' => 'trim|required|alpha-dash'])
			->db_update_if_ok(self::table, ['name','native','active'], 'code="'._es($a['code']).'"')
			->on_after_update(function() {
				cache_del([self::table]);
				common()->admin_wall_add(['language updated: '.$_POST['name'].'', $a['code']]);
			})
			->text('name')
			->text('native')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(['name' => 'trim|required|alpha-dash'])
			->db_insert_if_ok(self::table, ['name','code','native','active'], [])
			->on_after_update(function() {
				cache_del([self::table]);
				common()->admin_wall_add(['language added: '.$_POST['name'].'', db()->insert_id()]);
			})
			->text('code')
			->text('name')
			->text('native')
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
		$order_fields = [
			'code' => 'code',
			'name' => 'name',
			'native' => 'native',
		];
		$per_page = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000];
		return form($r, [
				'filter' => true,
			])
			->text('name')
			->text('native')
			->select_box('per_page', $per_page, ['class' => 'input-small'])
			->select_box('order_by', $order_fields, ['show_text' => 1, 'class' => 'input-medium'])
			->order_box()
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__languages_list ($params = []) {
// TODO
	}
}
