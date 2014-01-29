<?php

/**
* Core servers management
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_manage_servers {

	/**
	*/
	function show() {
		return table('SELECT * FROM '.db('core_servers'))
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
		$a = db()->query_fetch('SELECT * FROM '.db('core_servers').' WHERE id='.intval($_GET['id']));
		if (!$a['id']) {
			return _e('No id!');
		}
		$a = $_POST ? $a + $_POST : $a;
		return form($a)
			->validate(array('ip' => 'trim|required|valid_ip'))
			->db_update_if_ok('core_servers', array('ip','role','name','hostname','comment'), 'id='.$a['id'], array('on_after_update' => function() {
				cache_del(array('servers','server_roles'));
				common()->admin_wall_add(array('server updated: '.$_POST['ip'].'', $a['id']));
			}))
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
			->validate(array('ip' => 'trim|required|valid_ip|is_unique[core_servers.ip]'))
			->db_insert_if_ok('core_servers', array('ip','role','name','hostname','comment'), array(), array('on_after_update' => function() {
				cache_del(array('servers','server_roles'));
				common()->admin_wall_add(array('server added: '.$_POST['ip'].'', db()->insert_id()));
			}))
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
		return _class('admin_methods')->delete(array('table' => 'core_servers'));
	}

	/**
	*/
	function active() {
		return _class('admin_methods')->active(array('table' => 'core_servers'));
	}

	/**
	*/
	function _hook_widget__servers_list ($params = array()) {
// TODO
	}
}
