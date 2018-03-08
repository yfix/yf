<?php

/**
* Core servers management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_servers {

	const table = 'core_servers';

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db(self::table))
			->text('ip')
			->text('role')
			->text('name')
			->text('hostname')
			->text('comment')
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_add();
	}

	/**
	*/
	function edit() {
		$a = db()->query_fetch('SELECT * FROM '.db(self::table).' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(['ip' => 'trim|required|valid_ip'])
			->db_update_if_ok(self::table, ['ip','role','name','hostname','comment'], 'id='.$a['id'])
			->on_after_update(function() {
				cache_del(['servers','server_roles']);
				common()->admin_wall_add(['server updated: '.$_POST['ip'].'', $a['id']]);
			})
			->text('ip')
			->text('role')
			->text('name')
			->text('hostname')
			->textarea('comment')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function add() {
		$a = $_POST;
		return form($a)
			->validate(['ip' => 'trim|required|valid_ip|is_unique[core_servers.ip]'])
			->db_insert_if_ok(self::table, ['ip','role','name','hostname','comment'], [])
			->on_after_update(function() {
				cache_del(['servers','server_roles']);
				common()->admin_wall_add(['server added: '.$_POST['ip'].'', db()->insert_id()]);
			})
			->text('ip')
			->text('role')
			->text('name')
			->text('hostname')
			->textarea('comment')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		return _class('admin_methods')->delete(['table' => self::table]);
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(['table' => self::table]);
	}

	/**
	*/
	function _hook_widget__servers_list ($params = []) {
// TODO
	}
}
