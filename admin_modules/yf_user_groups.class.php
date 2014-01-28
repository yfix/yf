<?php

/**
* User groups editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_user_groups {

	/**
	*/
	function show() {
		$blocks = main()->get_data('blocks_names');
		foreach ((array)$blocks as $_id => $_info) {
			if ($_info['type'] == 'user' && $_info['name'] == 'center_area') {
				$block_center_id = $_id;
				break;
			}
		}
		$menu_id = db()->get_one('SELECT id FROM '.db('menus').' WHERE type="user" AND active=1 LIMIT 1');
		return table('SELECT * FROM '.db('user_groups').' ORDER BY id ASC', array(
				'custom_fields' => array('members_count' => 'SELECT `group`, COUNT(*) AS num FROM '.db('user').' GROUP BY `group`'),
			))
			->text('name')
			->text('go_after_login')
			->text('members_count', array('link' => './?object=manage_users&action=filter_save&page=clear&filter=group:%d', 'link_field_name' => 'id'))
			->btn_edit()
			->btn_delete()
			->btn_active()
			->footer_add()
			->footer_link('Blocks', './?object=blocks&action=show_rules&id='.$block_center_id)
			->footer_link('Menu', './?object=menus_editor&action=show_items&id='.$menu_id)
			->footer_link('Auth fails', './?object=log_user_auth_fails')
		;
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
			->db_insert_if_ok('user_groups', array('name','go_after_login','active'), array(), array('on_after_update' => function() {
				cache_del(array('user_groups', 'user_groups_details'));
				common()->admin_wall_add(array('user group added: '.$_POST['name'].'', db()->insert_id()));
			}))
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
		$a = db()->query_fetch('SELECT * FROM '.db('user_groups').' WHERE id='.intval($_GET['id']));
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|alpha_numeric|is_unique[admin_groups.name]'
			))
			->db_update_if_ok('user_groups', array('name','go_after_login'), 'id='.$id, array('on_after_update' => function() {
				cache_del(array('user_groups', 'user_groups_details'));
				common()->admin_wall_add(array('user group edited: '.$_POST['name'].'', $id));
			}))
			->text('name','Group name')
			->text('go_after_login','Url after login')
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			db()->query('DELETE FROM '.db('user_groups').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('user group deleted: '.$_GET['id'].'', $_GET['id']));
		}
		cache_del(array('user_groups', 'user_groups_details'));
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
			$group_info = db()->query_fetch('SELECT * FROM '.db('user_groups').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($group_info)) {
			db()->UPDATE('user_groups', array(
				'active'	=> intval(!$group_info['active']),
			), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('user group: '.$group_info['name'].' '.($group_info['active'] ? 'inactivated' : 'activated'), $group_info['id']));
		}
		cache_del(array('user_groups', 'user_groups_details'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($group_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function _hook_widget__user_groups ($params = array()) {
// TODO
	}
}
