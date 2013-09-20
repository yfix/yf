<?php

/**
* Admin groups handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_groups {

	/**
	*/
	function show () {
		$blocks = main()->get_data('blocks_names');
		foreach ((array)$blocks as $_id => $_info) {
			if ($_info['type'] == 'admin' && $_info['name'] == 'center_area') {
				$admin_center_id = $_id;
				break;
			}
		}
		$menu_id = db()->get_one('SELECT id FROM '.db('menus').' WHERE type="admin" AND active="1" LIMIT 1');
		return table('SELECT * FROM '.db('admin_groups').' ORDER BY id ASC')
			->text('name')
			->text('go_after_login')
			->btn_edit()
			->btn_delete()
			->btn_active()
			->footer_add()
			->footer_link('Blocks', './?object=blocks&action=show_rules&id='.$admin_center_id)
			->footer_link('Menu', './?object=menus_editor&action=show_items&id='.$menu_id);
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|alpha_numeric|is_unique[admin_groups.name]'
			))
			->db_insert_if_ok('admin_groups', array('name','go_after_login','active'), array(), array(
				'on_after_update' => function() {
					cache()->refresh(array('admin_groups', 'admin_groups_details'));
					common()->admin_wall_add(array('admin group added: '.$_POST['name'].'', db()->insert_id()));
				},
			))
			->text('name','Group name')
			->text('go_after_login','Url after login')
			->active_box()
			->save_and_back();
	}

	/**
	* Edit groups
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('admin_groups').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|alpha_numeric|is_unique[admin_groups.name]'
			))
			->db_update_if_ok('admin_groups', array('name','go_after_login'), 'id='.$id, array(
				'on_after_update' => function() {
					cache()->refresh(array('admin_groups', 'admin_groups_details'));
					common()->admin_wall_add(array('admin group edited: '.$_POST['name'].'', $id));
				},
			))
			->text('name','Group name')
			->text('go_after_login','Url after login')
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] == 1) {
			$_GET['id'] = 0;
		}
		if (!empty($_GET['id'])) {
			db()->query('DELETE FROM '.db('admin_groups').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('admin group deleted', $_GET['id']));
		}
		cache()->refresh(array('admin_groups', 'admin_groups_details'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function active() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$group_info = db()->query_fetch('SELECT * FROM '.db('admin_groups').' WHERE id='.intval($_GET['id']));
		}
		if ($_GET['id'] == 1) {
			$group_info = array();
		}
		if (!empty($group_info)) {
			db()->UPDATE('admin_groups', array('active'	=> intval(!$group_info['active'])), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('admin group '.$group_info['name'].' '.($group_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		cache()->refresh(array('admin_groups', 'admin_groups_details'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($group_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function _hook_wall_link($msg = array()) {
		$action = $msg['action'] == 'delete' ? 'show' : 'edit';
		return './?object=admin_groups&action='.$action.'&id='.$msg['object_id'];
	}

	function _hook_widget__admin_groups ($params = array()) {
// TODO
	}

}
