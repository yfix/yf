<?php

/**
* Blocks editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blocks {

	/**
	*/
	function _init () {
		$array_all = array('' => '-- ALL --');

		$this->_methods['user'] = $array_all;
		foreach ((array)module('user_modules')->_get_methods() as $module_name => $module_methods) {
			$this->_user_methods[$module_name] = $module_name.' -> -- ALL --';
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_methods['user'][$module_name.'.'.$method_name] = $module_name.' -> '.$method_name;
			}
		}

		$this->_methods['admin'] = $array_all;
		foreach ((array)module('admin_modules')->_get_methods() as $module_name => $module_methods) {
			$this->_admin_methods[$module_name] = $module_name.' -> -- ALL --';
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_methods['admin'][$module_name.'.'.$method_name] = $module_name.' -> '.$method_name;
			}
		}

		$this->_themes = $array_all;
		foreach ((array)module('template_editor')->_get_themes_names() as $_location => $_themes) {
			foreach ((array)$_themes as $_theme) {
				$this->_themes[$_theme] = $_theme;
			}
		}

		$this->_groups['user'] = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('user_groups').' WHERE active="1"');
		$this->_groups['admin'] = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('admin_groups').' WHERE active="1"');
		$this->_locales = $array_all + (array)module('locale_editor')->_get_locales();
		$this->_sites = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('sites').' WHERE active="1"');
		$this->_servers = $array_all + (array)db()->get_2d('SELECT id,name FROM '.db('core_servers').' WHERE active="1"');
	}

	/**
	*/
	function show () {
		return table('SELECT * FROM '.db('blocks').' ORDER BY type DESC, name ASC', array('custom_fields' => array(
				'num_rules' => 'SELECT block_id, COUNT(*) AS num FROM '.db('block_rules').' GROUP BY block_id'
			)))
			->link('name', './?object='.$_GET['object'].'&action=show_rules&id=%d', '', array('link_field_name' => 'id'))
			->text('type')
			->text('num_rules')
			->text('stpl_name', 'Template')
			->text('method_name', 'Method')
			->btn('Rules', './?object='.$_GET['object'].'&action=show_rules&id=%d')
			->btn_edit()
			->btn_delete()
			->btn_clone()
			->btn('Export', './?object='.$_GET['object'].'&action=export&id=%d')
			->btn_active()
			->footer_add('', './?object='.$_GET['object'].'&action=add')
		;
	}

	/**
	*/
	function add () {
		$a = $_POST;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name'	=> 'trim|required|alpha_numeric|is_unique[blocks.name]',
				'type'	=> 'trim|required',
			))
			->db_insert_if_ok('blocks', array('type','name','desc','stpl_name','method_name','active'), array(), array('on_after_update' => function() {
				common()->admin_wall_add(array('block added: '.$_POST['name'].'', db()->insert_id()));
				cache()->refresh('blocks_names');
			}))
			->select_box('type', array('admin' => 'admin', 'user' => 'user'))
			->text('name','Block name')
			->text('desc','Block Description')
			->template_select_box('stpl_name','Custom template')
			->method_select_box('method_name','Custom class method')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit () {
		$a = db()->get('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']).' OR name="'._es($_GET['id']).'"');
		$_GET['id'] = $a['id'];
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a)
			->validate(array(
				'name'	=> 'trim|required|alpha_numeric|is_unique[blocks.name]',
				'type'	=> 'trim|required',
			))
			->db_update_if_ok('blocks', array('name','desc','stpl_name','method_name','active'), 'id='.$_GET['id'], array('on_after_update' => function() {
				common()->admin_wall_add(array('block updated: '.$_POST['name'].'', $id));
				cache()->refresh('blocks_names');
			}))
			->text('name','Block name')
			->text('desc','Block Description')
			->template_select_box('stpl_name','Custom template')
			->method_select_box('method_name','Custom class method')
			->active_box()
			->save_and_back();
	}

	/**
	* Get array of templates for the given init type
	*/
	function _get_stpls ($type = 'user') {
		return module('template_editor')->_get_stpls_for_type($type);
	}

	/**
	* Delete block and its rules
	*/
	function delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($block_info['id'])) {
			db()->query('DELETE FROM '.db('blocks').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('block_rules').' WHERE block_id='.intval($_GET['id']));
			common()->admin_wall_add(array('block deleted: '.$block_info['name'].'', $_GET['id']));
		}
		cache()->refresh(array('blocks_names', 'blocks_rules'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function clone_item () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('No id!');
		}
		$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		$sql = $block_info;
		unset($sql['id']);
		$sql['name'] = $sql['name'].'_clone';

		db()->INSERT('blocks', $sql);
		$NEW_BLOCK_ID = db()->INSERT_ID();

		$Q = db()->query('SELECT * FROM '.db('block_rules').' WHERE block_id='.intval($_GET['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			unset($_info['id']);
			$_info['block_id'] = $NEW_BLOCK_ID;

			db()->INSERT('block_rules', $_info);

			$NEW_ITEM_ID = db()->INSERT_ID();
		}
		common()->admin_wall_add(array('block cloned: '.$_info['name'].' from '.$block_info['name'], $NEW_ITEM_ID));
		cache()->refresh(array('blocks_names', 'blocks_rules'));
		return js_redirect('./?object='.$_GET['object']);
	}

	/**
	*/
	function active () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($block_info['id'])) {
			db()->UPDATE('blocks', array('active' => (int)!$block_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('block '.$block_info['name'].' '.($block_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		cache()->refresh(array('blocks_names', 'blocks_rules'));
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($block_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	* Rules list for given block id
	*/
	function show_rules () {
		$block_info = db()->get('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		if (empty($block_info['id'])) {
			return _e('No such block!');
		}
		$_GET['id'] = $block_info['id'];
		return table('SELECT * FROM '.db('block_rules').' WHERE block_id='.intval($_GET['id']), array('caption' => ''/*$block_info['type'].'::'.$block_info['name']*/))
			->text('order')
			->allow_deny('rule_type')
			->data('methods', $this->_methods[$block_info['type']])
			->data('user_groups', $this->_groups[$block_info['type']], array('desc' => 'Groups'))
			->data('themes', $this->_themes)
			->data('locales', $this->_locales)
			->data('site_ids', $this->_sites, array('desc' => 'Sites'))
			->data('server_ids', $this->_servers, array('desc' => 'Servers'))
			->btn_edit('', './?object='.$_GET['object'].'&action=edit_rule&id=%d')
			->btn_delete('', './?object='.$_GET['object'].'&action=delete_rule&id=%d')
			->btn_clone('', './?object='.$_GET['object'].'&action=clone_rule&id=%d')
			->btn_active('', './?object='.$_GET['object'].'&action=activate_rule&id=%d')
			->footer_add('', './?object='.$_GET['object'].'&action=add_rule&id='.$block_info['id'])
		;
	}

	/**
	*/
	function add_rule () {
		$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		if (empty($block_info['id'])) {
			return _e('No such block!');
		}
		$_GET['id'] = intval($block_info['id']);

		$a = $_POST;

		$multi_selects = array('methods', 'user_groups', 'themes', 'locales', 'site_ids', 'server_ids');
		if ($_POST) {
			foreach ($multi_selects as $k) {
				$_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
			}
		}
		$a['redirect_link'] = './?object='.$_GET['object'];
		$a['type'] = $block_info['type'];
		return form($a)
			->validate(array(
				'rule_type'	=> 'trim|required',
			))
			->db_insert_if_ok('block_rules', array('rule_type','methods','user_groups','themes','locales','site_ids','server_ids','order','active'), array('block_id' => $block_info['id']), array(
				'on_after_update' => function() {
					common()->admin_wall_add(array('block rule added for '.$block_info['name'], $_GET['id']));
					cache()->refresh('blocks_rules');
				}
			))
			->info('type')
			->allow_deny_box('rule_type')
			->multi_select_box('methods', $this->_methods[$block_info['type']], array('edit_link' => './?object='.$block_info['type'].'_modules'))
			->multi_select_box('user_groups', $this->_groups[$block_info['type']], array('edit_link' => './?object='.$block_info['type'].'_groups', 'desc' => 'Groups'))
			->multi_select_box('themes', $this->_themes, array('edit_link' => './?object=template_editor'))
			->multi_select_box('locales', $this->_locales, array('edit_link' => './?object=locale_editor'))
			->multi_select_box('site_ids', $this->_site_ids, array('edit_link' => './?object=manage_sites', 'desc' => 'Sites'))
			->multi_select_box('server_ids', $this->_server_ids, array('edit_link' => './?object=manage_servers', 'desc' => 'Servers'))
			->number('order', 'Rule Processing Order')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit_rule () {
		$rule_info = db()->get('SELECT * FROM '.db('block_rules').' WHERE id='.intval($_GET['id']));
		if (empty($rule_info['id'])) {
			return _e('No such rule!');
		}
		$_GET['id'] = $rule_info['id'];

		$block_info = db()->get('SELECT * FROM '.db('blocks').' WHERE id='.intval($rule_info['block_id']));
		if (empty($block_info['id'])) {
			return _e('No such block!');
		}
		$a = $rule_info;

		$multi_selects = array('methods', 'user_groups', 'themes', 'locales', 'site_ids', 'server_ids');
		if ($_POST) {
			foreach ($multi_selects as $k) {
				$_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
			}
		} else {
			foreach ($multi_selects as $k) {
				$a[$k] = $this->_multi_db_to_html($a[$k]);
			}
		}
		$a['redirect_link'] = './?object='.$_GET['object'];
		$a['type'] = $block_info['type'];
		return form($a)
			->validate(array(
				'rule_type'	=> 'trim|required',
			))
			->db_update_if_ok('block_rules', array('rule_type','methods','user_groups','themes','locales','site_ids','server_ids','order','active'), 'id='.$rule_info['id'], array(
				'on_after_update' => function() {
					common()->admin_wall_add(array('block rule updated for: '.$block_info['name'], $_GET['id']));
					cache()->refresh('blocks_rules');
				}
			))
			->info('type')
			->allow_deny_box('rule_type')
			->multi_select_box('methods', $this->_methods[$block_info['type']], array('edit_link' => './?object='.$block_info['type'].'_modules'))
			->multi_select_box('user_groups', $this->_groups[$block_info['type']], array('edit_link' => './?object='.$block_info['type'].'_groups', 'desc' => 'Groups'))
			->multi_select_box('themes', $this->_themes, array('edit_link' => './?object=template_editor'))
			->multi_select_box('locales', $this->_locales, array('edit_link' => './?object=locale_editor'))
			->multi_select_box('site_ids', $this->_site_ids, array('edit_link' => './?object=manage_sites', 'desc' => 'Sites'))
			->multi_select_box('server_ids', $this->_server_ids, array('edit_link' => './?object=manage_servers', 'desc' => 'Servers'))
			->number('order', 'Rule Processing Order')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete_rule () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$rule_info = db()->query_fetch('SELECT * FROM '.db('block_rules').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($rule_info['id'])) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($rule_info['block_id']));
		}
		if (!empty($block_info['id'])) {
			db()->query('DELETE FROM '.db('block_rules').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('block rule deleted for: '.$block_info['name'], $_GET['id']));
			cache()->refresh('blocks_rules');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_rules&id='.$block_info['id']);
		}
	}

	/**
	*/
	function clone_rule () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$rule_info = db()->query_fetch('SELECT * FROM '.db('block_rules').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($rule_info['id'])) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($rule_info['block_id']));
		}
		if (!$block_info) {
			return _e('No such rule or block');
		}
		$sql = $rule_info;
		unset($sql['id']);

		db()->INSERT('block_rules', $sql);
		$NEW_RULE_ID = db()->INSERT_ID();

		common()->admin_wall_add(array('block rule cloned for block '.$block_info['name'], $NEW_RULE_ID));
		cache()->refresh(array('blocks_names', 'blocks_rules'));
		return js_redirect('./?object='.$_GET['object'].'&action=show_rules&id='.$block_info['id']);
	}

	/**
	*/
	function activate_rule () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$rule_info = db()->query_fetch('SELECT * FROM '.db('block_rules').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($rule_info['id'])) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($rule_info['block_id']));
		}
		if (!empty($block_info['id'])) {
			db()->UPDATE('block_rules', array('active' => (int)!$rule_info['active']), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('block rule for '.$block_info['name'].' '.($rule_info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
			cache()->refresh(array('blocks_names', 'blocks_rules'));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($rule_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_rules&id='.$block_info['id']);
		}
	}

	/**
	* Export blocks items
	*/
	function export() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$block_info = db()->query_fetch('SELECT * FROM '.db('blocks').' WHERE id='.intval($_GET['id']));
		}
		$params = array(
			'single_table'	=> '',
			'tables'		=> array(db('blocks'), db('block_rules')),
			'full_inserts'	=> 1,
			'ext_inserts'	=> 1,
			'export_type'	=> 'insert',
			'silent_mode'	=> true,
		);
		if ($block_info['id']) {
			$params['where'] = array(
				db('blocks')		=> 'id='.intval($block_info['id']),
				db('block_rules')	=> 'block_id='.intval($block_info['id']),
			);
		}
		$EXPORTED_SQL = module('db_manager')->export($params);
		$replace = array(
			'sql_text'	=> _prepare_html($EXPORTED_SQL, 0),
			'back_link'	=> './?object='.$_GET['object'],
		);
		return tpl()->parse('db_manager/export_text_result', $replace);
	}

	/**
	*/
	function _multi_html_to_db($input = array()) {
		if (is_array($input)) {
			$input = ','.implode(',', $input).',';
		}
		return (string)str_replace(array(' ',"\t","\r","\n",',,'), '', $input);
	}

	/**
	*/
	function _multi_db_to_html($input = '') {
		if (!is_array($input)) {
			$input	= explode(',',str_replace(array(' ',"\t","\r","\n",',,'), '', $input));
		}
		$output = array();
		foreach ((array)$input as $v) {
			if ($v) {
				$output[$v] = $v;
			}
		}
		return (array)$output;
	}

	/**
	*/
	function _hook_wall_link($msg = array()) {
		$action = $msg['action'] == 'activate_block' ? 'edit' : 'show';
		return './?object=blocks&action='.$action.'&id='.$msg['object_id'];
	}

	/**
	*/
	function _hook_widget__user_blocks ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__admin_blocks ($params = array()) {
// TODO
	}
}
