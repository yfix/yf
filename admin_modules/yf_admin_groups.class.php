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
		$blocks = main()->get_data('blocks_all');
		foreach ((array)$blocks as $_id => $_info) {
			if ($_info['type'] == 'admin' && $_info['name'] == 'center_area') {
				$admin_center_id = $_id;
				break;
			}
		}
		$gid = main()->ADMIN_GROUP;
		$func = function($row) use ($gid) {
			return !($row['id'] == $gid);
		};
		$menu_id = db()->get_one('SELECT id FROM '.db('menus').' WHERE type="admin" AND active=1 LIMIT 1');
		return table('SELECT * FROM '.db('admin_groups').' ORDER BY id ASC', array(
				'custom_fields' => array('members_count' => 'SELECT `group`, COUNT(*) AS num FROM '.db('admin').' GROUP BY `group`'),
			))
			->text('name')
			->text('go_after_login')
			->text('members_count', array('link' => './?object=admin&action=filter_save&page=clear&filter=group:%d', 'link_field_name' => 'id'))
			->btn_edit()
			->btn_delete(array('display_func' => $func))
			->btn_active(array('display_func' => $func))
			->footer_add()
			->footer_link('Blocks', url_admin('/blocks/show_rules/'.$admin_center_id))
			->footer_link('Menu', url_admin('/menus_editor/show_items/'.$menu_id))
			->footer_link('Auth fails', url_admin('/log_admin_auth_fails'))
		;
	}

	/**
	*/
	function add() {
		$a = $_POST;
		$a['redirect_link'] = url_admin('/@object');
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|alpha_dash|is_unique[admin_groups.name]'
			))
			->db_insert_if_ok('admin_groups', array('name','go_after_login','active'), array())
			->on_after_update(function() {
				cache_del(array('admin_groups', 'admin_groups_details'));
				common()->admin_wall_add(array('admin group added: '.$_POST['name'].'', db()->insert_id()));
			})
			->text('name','Group name')
			->text('go_after_login','Url after login')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('admin_groups').' WHERE id='.intval($_GET['id']));
		$a = (array)$_POST + (array)$a;
		$a['redirect_link'] = url_admin('/@object');
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|alpha_dash|is_unique_without[admin_groups.name.'.$id.']'
			))
			->db_update_if_ok('admin_groups', array('name','go_after_login'), 'id='.$id)
			->on_after_update(function() {
				cache_del(array('admin_groups', 'admin_groups_details'));
				common()->admin_wall_add(array('admin group edited: '.$_POST['name'].'', $id));
			})
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
		cache_del(array('admin_groups', 'admin_groups_details'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect(url_admin('/@object'));
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
		cache_del(array('admin_groups', 'admin_groups_details'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($group_info['active'] ? 0 : 1);
		} else {
			return js_redirect(url_admin('/@object'));
		}
	}

	/**
	*/
	function _hook_wall_link($msg = array()) {
		$action = $msg['action'] == 'delete' ? 'show' : 'edit';
		return url_admin('/admin_groups/'.$action.'/'.$msg['object_id']);
	}

	function _hook_widget__admin_groups ($params = array()) {
// TODO
	}

}
